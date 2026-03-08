<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title> ChatBot Roʞka Sports</title>
    <link href="../Bootstrap5/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/chat.css">
</head>

<body class="bg-dark text-light">
    <div class="container-fluid vh-100 d-flex flex-column">
        <!-- Header con Ainhoa -->
       <header class="chat-header bg-gradient-roka text-center py-2"> <!-- py-3 → py-2 -->
         <a href="../menu/menu.php" class="position-absolute top-0 start-0 m-2 btn btn-sm btn-outline-light inicio-btn">
        <i class="fas fa-home me-1"></i>Inicio
    </a>
    <img src="../img/ainhoa.jpeg" alt="Ainhoa AI" class="ainhoa-logo mb-1">
    <h1 class="h4 mb-0 fw-bold">Ainhoa AI</h1>
    <p class="mb-0 small opacity-75">Tu asistente de Roʞka</p>
</header>





        <!-- Chat Messages -->
        <main id="chatMessages" class="flex-grow-1 p-4 overflow-auto"></main>

        <!-- Typing Indicator -->
        <div id="typingIndicator" class="message bot-message mb-3 typing-container" style="display:none;">
            <div class="message-content">
                <div class="typing">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <!-- Input -->
        <footer class="chat-input p-4 bg-black">
            <div class="input-group">
                <input type="text" id="messageInput" class="form-control bg-dark text-light border-roka"
                    placeholder="¿En qué puedo ayudarte?" autocomplete="off">
                <button id="sendBtn" class="btn btn-roka" type="button">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="commands mt-2 small text-muted">
                <span class="cmd-badge" data-cmd="stockcritico">/stockcritico</span>
                <span class="cmd-badge" data-cmd="clientes">/clientes</span>
                <span class="cmd-badge" data-cmd="ventas">/ventas</span>
                <span class="cmd-badge" data-cmd="mermas">/mermas</span>
                <span class="cmd-badge" data-cmd="precioprod">/precioprod</span>
                <span class="cmd-badge" data-cmd="ganancias">/ganancias</span>
                <span class="cmd-badge" data-cmd="alertas">/alertas</span>
                <span class="cmd-badge" data-cmd="ventashoy">/ventashoy</span>
                <span class="cmd-badge" data-cmd="productos">/productos</span>
                <span class="cmd-badge" data-cmd="materiales">/materiales</span>
                <span class="cmd-badge" data-cmd="ayuda">/ayuda</span>
                <span class="cmd-badge" data-cmd="creditos">/creditos</span>
                <span class="cmd-badge" data-cmd="cxp">/pagos a proveedor</span>
            </div>


            <div class="mt-2 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-circle text-success small"></i> Conectado
                </small>
                <button class="btn btn-sm btn-outline-light clear-chat">
                    <i class="fas fa-trash"></i> Limpiar
                </button>
            </div>


        </footer>
    </div>

    <script src="../js/jquery-3.4.1.min.js"></script>
    <script src="../Bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script>
        // CLICK BADGES → COPIA AL INPUT
        $(document).on('click', '.cmd-badge', function() {
            const cmd = $(this).data('cmd');
            $('#messageInput').val('/' + cmd).focus();
            sendMessage(); // Envía automáticamente
        });

        $(function() {
            let typing = false;

            // 🆕 Detectar conexión
            function updateStatus(connected) {
                $('.fa-circle').removeClass('text-success text-danger').addClass(connected ? 'text-success' : 'text-danger');
            }

            // 1️⃣ AGREGAR MENSAJE
            function addMessage(sender, message) {
                const msgClass = sender === 'user' ? 'user-message' : 'bot-message';
                const time = new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                $('#chatMessages').append(
                    `<div class="message ${msgClass} mb-3">
                <div class="message-content">${message}</div>
                <div class="message-time small text-muted">${time}</div>
            </div>`
                );
                scrollToBottom();
            }

            // 2️⃣ MOSTRAR "ESCRIBIENDO..."
            function showTyping() {
                if (typing) return;
                typing = true;
                $('#typingIndicator').show();
                scrollToBottom();
            }

            // 3️⃣ OCULTAR "ESCRIBIENDO..."
            function hideTyping() {
                typing = false;
                $('#typingIndicator').hide();
            }

            // 4️⃣ DESPLAZAR AL FINAL
            function scrollToBottom() {
                $('#chatMessages').scrollTop($('#chatMessages')[0].scrollHeight);
            }

            // 5️⃣ ESCAPAR HTML (SEGURIDAD)
            function escapeHtml(text) {
                return text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // 6️⃣ ENVIAR MENSAJE (FUNCIÓN PRINCIPAL)
            function sendMessage() {
                const $input = $('#messageInput');
                const text = $input.val().trim();
                if (!text) return;

                addMessage('user', escapeHtml(text));
                $input.val('').focus();
                showTyping();

                $.post('chat_handler.php', {
                        message: text
                    }, null, 'json')
                    .done(function(response) {
                        hideTyping();
                        updateStatus(true);
                        if (response && response.reply) {
                            addMessage('bot', response.reply);
                        } else {
                            addMessage('bot', '⚠️ Error: Respuesta inválida.');
                        }
                    })
                    .fail(function(xhr) {
                        hideTyping();
                        updateStatus(false);
                        console.error('AJAX Error:', xhr);
                        addMessage('bot', '❌ Error conexión. Verifica XAMPP.');
                    });
            }

            // 🔥 EVENTOS
            $('#messageInput').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    sendMessage();
                }
            }).focus();

            $('#sendBtn').on('click', sendMessage);

            $(document).on('click', '.clear-chat', function() {
                $('#chatMessages').empty();
                addMessage('bot', '🧹 Chat limpio. Usa <span class="badge bg-secondary">/ayuda</span> para comandos.');
            });

            // Auto-resize input
            $('#messageInput').on('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });

            // Mensaje de bienvenida
           addMessage('bot', 
    `<div style="text-align: center;">
        <div class="mb-3">
            ✨ <strong>¡Hola Charlero! 👋</strong><br>
            Soy <span class="ainhoa-name">Ainhoa AI</span><br>
            <small class="opacity-75">Tu asistente de Roʞka Sports</small>
        </div>
        <div class="welcome-grid">
            <span class="cmd-badge" data-cmd="Ventashoy">📊 Ventas Hoy</span>
            <span class="cmd-badge" data-cmd="ganancias">💰 Ingresos</span>
            <span class="cmd-badge" data-cmd="stockcritico">🚨 Stock</span>
            <span class="cmd-badge" data-cmd="clientes">👥 Clientes</span>
            <span class="cmd-badge" data-cmd="ayuda">❓ Ayuda</span>
        </div>
    </div>`
);


        });
    </script>


</body>

</html>