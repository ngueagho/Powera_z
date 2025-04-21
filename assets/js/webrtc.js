/**
 * Gestion des appels audio/vidéo avec WebRTC
 * HouseConnect - Application de location immobilière
 */

// Variables globales
let localStream = null;
let peerConnection = null;
let remoteStream = null;
let callInProgress = false;
let isCaller = false;
let callType = 'audio'; // 'audio' ou 'video'

// Configuration des serveurs STUN/TURN (pour traverser les NAT/Firewalls)
const iceServers = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' }, // Serveur STUN public de Google
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' }
        // On pourrait ajouter des serveurs TURN pour une meilleure fiabilité
        // mais cela nécessite généralement un abonnement payant
    ]
};

/**
 * Initialiser l'interface d'appel
 * @param {string} localVideoId ID de l'élément vidéo local
 * @param {string} remoteVideoId ID de l'élément vidéo distant
 * @param {string} callButtonId ID du bouton d'appel
 * @param {string} hangupButtonId ID du bouton de fin d'appel
 * @param {string} toggleAudioId ID du bouton pour activer/désactiver l'audio
 * @param {string} toggleVideoId ID du bouton pour activer/désactiver la vidéo
 * @param {function} onCallStart Fonction appelée au démarrage de l'appel
 * @param {function} onCallEnd Fonction appelée à la fin de l'appel
 */
function initCallInterface(
    localVideoId, 
    remoteVideoId, 
    callButtonId, 
    hangupButtonId, 
    toggleAudioId, 
    toggleVideoId,
    onCallStart = null,
    onCallEnd = null
) {
    // Récupérer les éléments du DOM
    const localVideo = document.getElementById(localVideoId);
    const remoteVideo = document.getElementById(remoteVideoId);
    const callButton = document.getElementById(callButtonId);
    const hangupButton = document.getElementById(hangupButtonId);
    const toggleAudioButton = document.getElementById(toggleAudioId);
    const toggleVideoButton = document.getElementById(toggleVideoId);
    
    // Vérifier que tous les éléments existent
    if (!localVideo || !remoteVideo || !callButton || !hangupButton) {
        console.error('Éléments manquants pour l\'interface d\'appel');
        return;
    }
    
    // Configurer les événements des boutons
    callButton.addEventListener('click', () => {
        startCall(callType, localVideo, remoteVideo, onCallStart);
    });
    
    hangupButton.addEventListener('click', () => {
        endCall(onCallEnd);
    });
    
    if (toggleAudioButton) {
        toggleAudioButton.addEventListener('click', toggleAudio);
    }
    
    if (toggleVideoButton) {
        toggleVideoButton.addEventListener('click', toggleVideo);
    }
    
    // Si nous sommes appelés, configurer pour recevoir l'appel
    if (!isCaller) {
        setupCallReceiver(localVideo, remoteVideo, onCallStart, onCallEnd);
    }
}

/**
 * Démarrer un appel
 * @param {string} type Type d'appel ('audio' ou 'video')
 * @param {HTMLElement} localVideoElement Élément vidéo local
 * @param {HTMLElement} remoteVideoElement Élément vidéo distant
 * @param {function} onCallStart Fonction appelée au démarrage de l'appel
 */
async function startCall(type, localVideoElement, remoteVideoElement, onCallStart = null) {
    if (callInProgress) {
        console.warn('Un appel est déjà en cours');
        return;
    }
    
    try {
        callType = type;
        isCaller = true;
        
        // Demander l'accès à la caméra et/ou au microphone
        const constraints = {
            audio: true,
            video: type === 'video'
        };
        
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Afficher le flux local
        localVideoElement.srcObject = localStream;
        
        // Créer la connexion peer
        createPeerConnection(remoteVideoElement);
        
        // Ajouter les pistes locales à la connexion
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
        
        // Créer et envoyer l'offre
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        
        // Envoyer l'offre au serveur (qui la transmettra au destinataire)
        sendSignalingMessage({
            type: 'offer',
            offer: offer,
            callType: callType
        });
        
        callInProgress = true;
        
        // Callback de démarrage d'appel
        if (onCallStart) {
            onCallStart(callType);
        }
        
        // Enregistrer l'appel dans la base de données
        recordCall('started');
        
    } catch (error) {
        console.error('Erreur lors du démarrage de l\'appel:', error);
        endCall();
    }
}

/**
 * Créer une connexion peer
 * @param {HTMLElement} remoteVideoElement Élément vidéo distant
 */
function createPeerConnection(remoteVideoElement) {
    peerConnection = new RTCPeerConnection(iceServers);
    
    // Écouter les événements ICE (pour la connexion)
    peerConnection.onicecandidate = event => {
        if (event.candidate) {
            sendSignalingMessage({
                type: 'ice-candidate',
                candidate: event.candidate
            });
        }
    };
    
    // Écouter la connexion et déconnexion ICE
    peerConnection.oniceconnectionstatechange = () => {
        console.log('État de la connexion ICE:', peerConnection.iceConnectionState);
        
        if (peerConnection.iceConnectionState === 'disconnected' || 
            peerConnection.iceConnectionState === 'failed' || 
            peerConnection.iceConnectionState === 'closed') {
            
            endCall();
        }
    };
    
    // Écouter les pistes entrantes
    peerConnection.ontrack = event => {
        // Créer un flux distant s'il n'existe pas
        if (!remoteStream) {
            remoteStream = new MediaStream();
            remoteVideoElement.srcObject = remoteStream;
        }
        
        // Ajouter la piste au flux distant
        remoteStream.addTrack(event.track);
    };
}

/**
 * Configurer la réception d'appel
 * @param {HTMLElement} localVideoElement Élément vidéo local
 * @param {HTMLElement} remoteVideoElement Élément vidéo distant
 * @param {function} onCallStart Fonction appelée au démarrage de l'appel
 * @param {function} onCallEnd Fonction appelée à la fin de l'appel
 */
function setupCallReceiver(localVideoElement, remoteVideoElement, onCallStart = null, onCallEnd = null) {
    // Configurer la fonction qui recevra les messages de signalisation
    window.receiveSignalingMessage = async function(message) {
        try {
            // Si c'est une offre d'appel
            if (message.type === 'offer') {
                // Afficher l'interface d'appel entrant
                showIncomingCallUI(message.callType, async function(accepted) {
                    if (accepted) {
                        await acceptCall(message, localVideoElement, remoteVideoElement, onCallStart);
                    } else {
                        rejectCall();
                    }
                });
            } 
            // Si c'est un candidat ICE
            else if (message.type === 'ice-candidate' && peerConnection) {
                await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
            } 
            // Si c'est une réponse
            else if (message.type === 'answer' && peerConnection) {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(message.answer));
            } 
            // Si c'est une fin d'appel
            else if (message.type === 'hang-up') {
                endCall(onCallEnd);
            }
        } catch (error) {
            console.error('Erreur lors de la réception du message de signalisation:', error);
        }
    };
}

/**
 * Accepter un appel entrant
 * @param {object} offerMessage Message d'offre
 * @param {HTMLElement} localVideoElement Élément vidéo local
 * @param {HTMLElement} remoteVideoElement Élément vidéo distant
 * @param {function} onCallStart Fonction appelée au démarrage de l'appel
 */
async function acceptCall(offerMessage, localVideoElement, remoteVideoElement, onCallStart = null) {
    try {
        isCaller = false;
        callType = offerMessage.callType;
        
        // Demander l'accès à la caméra et/ou au microphone
        const constraints = {
            audio: true,
            video: callType === 'video'
        };
        
        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Afficher le flux local
        localVideoElement.srcObject = localStream;
        
        // Créer la connexion peer
        createPeerConnection(remoteVideoElement);
        
        // Ajouter les pistes locales à la connexion
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
        });
        
        // Définir la description distante (l'offre)
        await peerConnection.setRemoteDescription(new RTCSessionDescription(offerMessage.offer));
        
        // Créer et envoyer la réponse
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        
        sendSignalingMessage({
            type: 'answer',
            answer: answer
        });
        
        callInProgress = true;
        
        // Callback de démarrage d'appel
        if (onCallStart) {
            onCallStart(callType);
        }
        
        // Enregistrer l'appel dans la base de données
        recordCall('answered');
        
    } catch (error) {
        console.error('Erreur lors de l\'acceptation de l\'appel:', error);
        rejectCall();
    }
}

/**
 * Rejeter un appel entrant
 */
function rejectCall() {
    sendSignalingMessage({
        type: 'hang-up',
        reason: 'rejected'
    });
    
    // Enregistrer l'appel comme rejeté
    recordCall('declined');
}

/**
 * Terminer un appel
 * @param {function} onCallEnd Fonction appelée à la fin de l'appel
 */
function endCall(onCallEnd = null) {
    if (callInProgress) {
        // Envoyer un message de fin d'appel
        sendSignalingMessage({
            type: 'hang-up',
            reason: 'ended'
        });
        
        // Enregistrer la fin de l'appel dans la base de données
        recordCall('ended');
    }
    
    // Arrêter les flux
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    // Fermer la connexion peer
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    remoteStream = null;
    callInProgress = false;
    
    // Réinitialiser les éléments vidéo
    const localVideo = document.getElementById('local-video');
    const remoteVideo = document.getElementById('remote-video');
    
    if (localVideo) localVideo.srcObject = null;
    if (remoteVideo) remoteVideo.srcObject = null;
    
    // Callback de fin d'appel
    if (onCallEnd) {
        onCallEnd();
    }
}

/**
 * Activer/désactiver l'audio
 */
function toggleAudio() {
    if (localStream) {
        const audioTracks = localStream.getAudioTracks();
        
        if (audioTracks.length > 0) {
            const enabled = audioTracks[0].enabled;
            audioTracks[0].enabled = !enabled;
            
            const toggleAudioButton = document.getElementById('toggle-audio');
            if (toggleAudioButton) {
                toggleAudioButton.innerHTML = audioTracks[0].enabled 
                    ? '<i class="fas fa-microphone"></i>' 
                    : '<i class="fas fa-microphone-slash"></i>';
            }
        }
    }
}

/**
 * Activer/désactiver la vidéo
 */
function toggleVideo() {
    if (localStream) {
        const videoTracks = localStream.getVideoTracks();
        
        if (videoTracks.length > 0) {
            const enabled = videoTracks[0].enabled;
            videoTracks[0].enabled = !enabled;
            
            const toggleVideoButton = document.getElementById('toggle-video');
            if (toggleVideoButton) {
                toggleVideoButton.innerHTML = videoTracks[0].enabled 
                    ? '<i class="fas fa-video"></i>' 
                    : '<i class="fas fa-video-slash"></i>';
            }
        }
    }
}

/**
 * Envoyer un message de signalisation
 * @param {object} message Message à envoyer
 */
function sendSignalingMessage(message) {
    // Ajouter les identifiants à l'objet message
    message.senderId = currentUserId;
    message.receiverId = callReceiverId;
    
    // Envoyer le message via AJAX
    fetch('../api/call-signaling.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(message)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Erreur d\'envoi du message de signalisation:', data.message);
        }
    })
    .catch(error => {
        console.error('Erreur réseau:', error);
    });
}

/**
 * Afficher l'interface d'appel entrant
 * @param {string} type Type d'appel ('audio' ou 'video')
 * @param {function} callback Fonction appelée avec la décision (true/false)
 */
function showIncomingCallUI(type, callback) {
    // Créer la modal d'appel entrant
    const modal = document.createElement('div');
    modal.className = 'call-modal';
    modal.innerHTML = `
        <div class="call-modal-content">
            <div class="call-modal-header">
                <h3>Appel ${type === 'video' ? 'vidéo' : 'audio'} entrant</h3>
            </div>
            <div class="call-modal-body">
                <div class="caller-info">
                    <img src="${callerAvatar || '../assets/images/default-avatar.jpg'}" alt="${callerName}" class="caller-avatar">
                    <h4>${callerName || 'Utilisateur'}</h4>
                </div>
                <p>Vous recevez un appel ${type === 'video' ? 'vidéo' : 'audio'}.</p>
            </div>
            <div class="call-modal-footer">
                <button class="btn btn-danger call-reject">Refuser</button>
                <button class="btn btn-success call-accept">Accepter</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Jouer la sonnerie
    const ringtone = new Audio('../assets/sounds/ringtone.mp3');
    ringtone.loop = true;
    ringtone.play();
    
    // Gérer les boutons
    const acceptButton = modal.querySelector('.call-accept');
    const rejectButton = modal.querySelector('.call-reject');
    
    acceptButton.addEventListener('click', () => {
        ringtone.pause();
        modal.remove();
        callback(true);
    });
    
    rejectButton.addEventListener('click', () => {
        ringtone.pause();
        modal.remove();
        callback(false);
    });
    
    // Timeout de 30 secondes
    setTimeout(() => {
        if (document.body.contains(modal)) {
            ringtone.pause();
            modal.remove();
            callback(false);
        }
    }, 30000);
}

/**
 * Enregistrer un appel dans la base de données
 * @param {string} status Statut de l'appel ('started', 'answered', 'declined', 'ended')
 */
function recordCall(status) {
    // Envoyer les informations de l'appel via AJAX
    fetch('../api/calls.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'record',
            caller_id: isCaller ? currentUserId : callReceiverId,
            receiver_id: isCaller ? callReceiverId : currentUserId,
            call_type: callType,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Erreur d\'enregistrement de l\'appel:', data.message);
        }
    })
    .catch(error => {
        console.error('Erreur réseau:', error);
    });
}

/**
 * Vérifier la compatibilité du navigateur pour WebRTC
 * @returns {boolean} Navigateur compatible ou non
 */
function checkWebRTCSupport() {
    return !!(navigator.mediaDevices && 
            navigator.mediaDevices.getUserMedia && 
            window.RTCPeerConnection);
}