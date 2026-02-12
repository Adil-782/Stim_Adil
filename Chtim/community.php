<?php
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/chat.css">

<div class="chat-container">
    <div class="chat-header">
        <h2>💬 Chat Communautaire</h2>
        <p class="chat-subtitle">Discutez avec la communauté stim</p>
    </div>

    <div class="chat-messages" id="chatMessages">
        <!-- Les messages seront affichés ici par JavaScript -->
    </div>

    <div class="chat-input-container">
        <div class="chat-input-wrapper">
            <input type="text" id="messageInput" class="chat-input" placeholder="Tapez votre message..."
                maxlength="500">
            <button class="chat-send-btn" id="sendBtn">
                <span>Envoyer</span>
            </button>
        </div>
    </div>
</div>

<script>
    // Messages simulés (côté client uniquement)
    const simulatedMessages = [
        {
            username: 'GabeN',
            avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Gabe',
            message: 'Bienvenue sur le chat stim ! 🎮',
            time: '10:30',
            role: 'admin'
        },
        {
            username: 'DarkSasuke',
            avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Sasuke',
            message: 'Quelqu\'un a essayé Half-Life 3 ? C\'est incroyable !',
            time: '10:32',
            role: 'user'
        },
        {
            username: 'HackerMan',
            avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Hacker',
            message: 'Oui ! Le gameplay est génial, vraiment worth the wait 😄',
            time: '10:35',
            role: 'user'
        },
        {
            username: 'NoobSaibot',
            avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Noob',
            message: 'Je galère sur Elden Ring Easy Mode... c\'est normal ? 😅',
            time: '10:40',
            role: 'user'
        },
        {
            username: 'DarkSasuke',
            avatar: 'https://api.dicebear.com/7.x/avataaars/svg?seed=Sasuke',
            message: 'Mdr même en Easy Mode tu galères ? 🤣',
            time: '10:42',
            role: 'user'
        }
    ];

    let messages = [...simulatedMessages];

    // Fonction pour afficher les messages
    function displayMessages() {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = '';

        messages.forEach((msg, index) => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';

            const roleClass = msg.role === 'admin' ? 'admin-badge' : 'user-badge';
            const roleName = msg.role === 'admin' ? 'Admin' : 'User';

            messageDiv.innerHTML = `
            <div class="message-avatar">
                <img src="${msg.avatar}" alt="${msg.username}">
            </div>
            <div class="message-content">
                <div class="message-header">
                    <span class="message-username">${msg.username}</span>
                    <span class="message-badge ${roleClass}">${roleName}</span>
                    <span class="message-time">${msg.time}</span>
                </div>
                <div class="message-text">${msg.message}</div>
            </div>
        `;

            chatMessages.appendChild(messageDiv);
        });

        // Scroll vers le bas
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Fonction pour ajouter un message (simulation)
    function addMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();

        if (message === '') return;

        const now = new Date();
        const time = now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0');

        const newMessage = {
            username: '<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Invité'; ?>',
            avatar: '<?php echo isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'https://api.dicebear.com/7.x/avataaars/svg?seed=Guest'; ?>',
            message: message,
            time: time,
            role: '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'user'; ?>'
        };

        messages.push(newMessage);
        displayMessages();

        input.value = '';

        // Message de confirmation
        setTimeout(() => {
            showNotification('Message envoyé ! (Affichage local uniquement)');
        }, 100);
    }

    // Notification temporaire
    function showNotification(text) {
        const notif = document.createElement('div');
        notif.className = 'chat-notification';
        notif.textContent = text;
        document.body.appendChild(notif);

        setTimeout(() => {
            notif.classList.add('show');
        }, 10);

        setTimeout(() => {
            notif.classList.remove('show');
            setTimeout(() => notif.remove(), 300);
        }, 2000);
    }

    // Event listeners
    document.getElementById('sendBtn').addEventListener('click', addMessage);
    document.getElementById('messageInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            addMessage();
        }
    });

    // Affichage initial
    displayMessages();

    // Simuler l'arrivée de nouveaux messages aléatoirement
    setInterval(() => {
        if (Math.random() < 0.1) { // 10% de chance toutes les 5 secondes
            const randomMessages = [
                'GG pour cette communauté ! 🎉',
                'Quelqu\'un pour une partie multi ?',
                'Le nouveau jeu est dispo !',
                'Trop bien ce chat 😎',
                'Hâte de tester les nouveaux challenges !'
            ];

            const randomUsers = [
                { name: 'Player123', seed: 'Player123' },
                { name: 'GamerPro', seed: 'GamerPro' },
                { name: 'stimFan', seed: 'stimFan' }
            ];

            const randomUser = randomUsers[Math.floor(Math.random() * randomUsers.length)];
            const randomMsg = randomMessages[Math.floor(Math.random() * randomMessages.length)];

            const now = new Date();
            const time = now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0');

            messages.push({
                username: randomUser.name,
                avatar: `https://api.dicebear.com/7.x/avataaars/svg?seed=${randomUser.seed}`,
                message: randomMsg,
                time: time,
                role: 'user'
            });

            displayMessages();
        }
    }, 5000);
</script>

<?php include 'includes/footer.php'; ?>
