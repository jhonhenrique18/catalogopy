<?php
// Função para buscar pop-up ativo
function getActivePopup($conn) {
    $sql = "SELECT * FROM promotional_popup 
            WHERE is_active = 1 
            AND (end_date IS NULL OR end_date > NOW()) 
            ORDER BY created_at DESC 
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Função para verificar se pop-up deve ser exibido
function shouldShowPopup($conn) {
    $popup = getActivePopup($conn);
    
    if (!$popup) {
        return false;
    }
    
    // Verificar se a imagem existe (local ou URL)
    $image_url = $popup['image_url'];
    
    // Se for uma URL válida, consideramos que existe
    if (filter_var($image_url, FILTER_VALIDATE_URL)) {
        return true;
    }
    
    // Se for um arquivo local, verificar se existe
    if (!file_exists($image_url)) {
        return false;
    }
    
    return true;
}

// Função para gerar HTML do pop-up
function generatePopupHTML($conn) {
    $popup = getActivePopup($conn);
    
    if (!$popup || !shouldShowPopup($conn)) {
        return '';
    }
    
    $title = htmlspecialchars($popup['title']);
    $image_url = htmlspecialchars($popup['image_url']);
    $popup_id = $popup['id'];
    
    return "
    <div id='promotional-popup' class='popup-overlay' data-popup-id='$popup_id'>
        <div class='popup-content'>
            <button class='popup-close' onclick='closePromotionalPopup()'>
                <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                    <line x1='18' y1='6' x2='6' y2='18'></line>
                    <line x1='6' y1='6' x2='18' y2='18'></line>
                </svg>
            </button>
            <div class='popup-image-container'>
                <img src='$image_url' alt='$title' class='popup-image'>
            </div>
        </div>
    </div>";
}

// Função para gerar CSS do pop-up
function generatePopupCSS() {
    return "
    <style>
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(5px);
    }
    
    .popup-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    
    .popup-content {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        transform: scale(0.8) translateY(20px);
        transition: all 0.3s ease;
    }
    
    .popup-overlay.show .popup-content {
        transform: scale(1) translateY(0);
    }
    
    .popup-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0, 0, 0, 0.7);
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10001;
        transition: all 0.3s ease;
        color: white;
    }
    
    .popup-close:hover {
        background: rgba(0, 0, 0, 0.9);
        transform: scale(1.1);
    }
    
    .popup-image-container {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .popup-image {
        width: 100%;
        height: auto;
        max-width: 320px;
        max-height: 450px;
        object-fit: cover;
        border-radius: 20px;
    }
    
    /* Otimizações para mobile */
    @media (max-width: 480px) {
        .popup-content {
            max-width: 95vw;
            max-height: 85vh;
            border-radius: 15px;
        }
        
        .popup-image {
            max-width: 300px;
            max-height: 400px;
        }
        
        .popup-close {
            top: 10px;
            right: 10px;
            width: 35px;
            height: 35px;
        }
    }
    
    /* Animação de entrada */
    @keyframes popupFadeIn {
        from {
            opacity: 0;
            transform: scale(0.8) translateY(30px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    /* Prevenir scroll do body quando popup está aberto */
    body.popup-open {
        overflow: hidden;
        position: fixed;
        width: 100%;
    }
    </style>";
}

// Função para gerar JavaScript do pop-up
function generatePopupJS() {
    return "
    <script>
    // Variáveis globais para controle do pop-up
    let popupShown = false;
    let popupElement = null;
    
    // Função para mostrar o pop-up
    function showPromotionalPopup() {
        if (popupShown) return;
        
        popupElement = document.getElementById('promotional-popup');
        if (!popupElement) return;
        
        // Prevenir scroll do body
        document.body.classList.add('popup-open');
        
        // Mostrar pop-up com animação
        setTimeout(() => {
            popupElement.classList.add('show');
        }, 100);
        
        popupShown = true;
        
        // Salvar no localStorage que já foi mostrado hoje
        const today = new Date().toDateString();
        localStorage.setItem('popup_shown_date', today);
        localStorage.setItem('popup_id', popupElement.dataset.popupId);
    }
    
    // Função para fechar o pop-up
    function closePromotionalPopup() {
        if (!popupElement) return;
        
        popupElement.classList.remove('show');
        
        setTimeout(() => {
            document.body.classList.remove('popup-open');
            if (popupElement && popupElement.parentNode) {
                popupElement.parentNode.removeChild(popupElement);
            }
        }, 300);
    }
    
    // Função para verificar se deve mostrar o pop-up
    function checkShouldShowPopup() {
        const popupElement = document.getElementById('promotional-popup');
        if (!popupElement) return false;
        
        const today = new Date().toDateString();
        const lastShownDate = localStorage.getItem('popup_shown_date');
        const lastPopupId = localStorage.getItem('popup_id');
        const currentPopupId = popupElement.dataset.popupId;
        
        // Mostrar se:
        // 1. Nunca foi mostrado antes
        // 2. Não foi mostrado hoje
        // 3. É um pop-up diferente do último mostrado
        if (!lastShownDate || 
            lastShownDate !== today || 
            lastPopupId !== currentPopupId) {
            return true;
        }
        
        return false;
    }
    
    // Inicializar pop-up quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        // Aguardar um pouco para garantir que a página carregou
        setTimeout(() => {
            if (checkShouldShowPopup()) {
                showPromotionalPopup();
            } else {
                // Se não deve mostrar, remover do DOM
                const popupElement = document.getElementById('promotional-popup');
                if (popupElement && popupElement.parentNode) {
                    popupElement.parentNode.removeChild(popupElement);
                }
            }
        }, 1500); // Mostrar após 1.5 segundos
    });
    
    // Fechar pop-up ao clicar fora dele
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('popup-overlay')) {
            closePromotionalPopup();
        }
    });
    
    // Fechar pop-up com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popupShown) {
            closePromotionalPopup();
        }
    });
    
    // Tornar função disponível globalmente
    window.closePromotionalPopup = closePromotionalPopup;
    </script>";
}
?> 