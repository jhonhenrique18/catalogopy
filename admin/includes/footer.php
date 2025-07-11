</div>
    
    <!-- Bootstrap JS e Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script personalizado -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // Fechar sidebar ao clicar fora em dispositivos m��veis
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 992 && sidebar.classList.contains('show') && 
                    !sidebar.contains(event.target) && event.target !== sidebarToggle) {
                    sidebar.classList.remove('show');
                }
            });
        });
        
        /**
         * Inicializa o sistema de logo do admin de forma segura
         */
        function initAdminLogo() {
            const logoContainer = document.getElementById('admin-logo-container');
            if (!logoContainer) return;
            
            // Tentar carregar logo da loja
            const img = new Image();
            img.onload = function() {
                // Se a imagem carregar com sucesso, substituir o fallback
                logoContainer.innerHTML = '<img src="../assets/images/default-logo.png" alt="Logo" class="sidebar-logo">';
            };
            img.onerror = function() {
                // Se falhar, manter o fallback (ícone)
                console.log('Logo não encontrada, usando fallback');
            };
            
            // Tentar carregar a imagem
            img.src = '../assets/images/default-logo.png';
            
            // Timeout de segurança - se não carregar em 3 segundos, manter fallback
            setTimeout(function() {
                if (!img.complete) {
                    console.log('Timeout na logo, mantendo fallback');
                }
            }, 3000);
        }
        
        // Inicializar logo quando documento estiver pronto
        document.addEventListener('DOMContentLoaded', initAdminLogo);
    </script>
</body>
</html>
