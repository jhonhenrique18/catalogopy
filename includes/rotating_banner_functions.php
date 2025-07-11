<?php
/**
 * Funções para gerenciar a barra rotativa
 */

/**
 * Buscar mensagens ativas da barra rotativa
 */
function getActiveBannerMessages($conn) {
    $query = "SELECT message, background_color, text_color FROM rotating_banner 
              WHERE is_active = 1 
              ORDER BY sort_order ASC, created_at DESC";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return [];
}

/**
 * Gerar HTML da barra rotativa
 */
function generateRotatingBannerHTML($conn) {
    $banners = getActiveBannerMessages($conn);
    
    if (empty($banners)) {
        return '';
    }
    
    $html = '<div id="rotating-banner" style="position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: 45px; overflow: hidden;">';
    
    foreach ($banners as $index => $banner) {
        $bgColor = htmlspecialchars($banner['background_color']);
        $textColor = htmlspecialchars($banner['text_color']);
        $message = htmlspecialchars($banner['message']);
        $opacity = $index === 0 ? '1' : '0';
        
        $html .= '<div class="banner-message" 
                       style="position: absolute; 
                              width: 100%; 
                              height: 100%; 
                              background-color: ' . $bgColor . '; 
                              color: ' . $textColor . '; 
                              display: flex; 
                              align-items: center; 
                              justify-content: center; 
                              font-weight: 500; 
                              font-size: 14px;
                              opacity: ' . $opacity . ';
                              transition: opacity 0.5s ease-in-out;
                              padding: 0 20px;
                              text-align: center;
                              box-sizing: border-box;">' . $message . '</div>';
    }
    
    $html .= '</div>';
    
    // JavaScript para rotação automática
    if (count($banners) > 1) {
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const bannerMessages = document.querySelectorAll(".banner-message");
            let currentIndex = 0;
            
            if (bannerMessages.length > 1) {
                setInterval(function() {
                    // Esconder mensagem atual
                    bannerMessages[currentIndex].style.opacity = "0";
                    
                    // Próxima mensagem
                    currentIndex = (currentIndex + 1) % bannerMessages.length;
                    
                    // Mostrar próxima mensagem
                    bannerMessages[currentIndex].style.opacity = "1";
                }, 4000); // Troca a cada 4 segundos
            }
        });
        </script>';
    }
    
    // CSS para ajustar o body
    $html .= '<style>
        body {
            padding-top: 45px !important;
        }
        
        @media (max-width: 768px) {
            #rotating-banner .banner-message {
                font-size: 12px !important;
                padding: 0 15px !important;
            }
        }
    </style>';
    
    return $html;
}

/**
 * Verificar se existe alguma mensagem ativa
 */
function hasActiveBanners($conn) {
    $query = "SELECT COUNT(*) as count FROM rotating_banner WHERE is_active = 1";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }
    
    return false;
}
?> 