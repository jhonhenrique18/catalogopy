<?php
// Incluir arquivo de verificação de autenticação
require_once 'includes/auth_check.php';

// Incluir conexão com banco de dados
require_once '../includes/db_connect.php';

// Verificar se há mensagem de sucesso
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Consultar categorias
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count,
          p.name as parent_name
          FROM categories c
          LEFT JOIN categories p ON c.parent_id = p.id
          ORDER BY c.name";
$result = $conn->query($query);

// Incluir cabeçalho
include 'includes/admin_layout.php';
?>

<!-- Conteúdo principal -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Categorias</h1>
        <a href="categoria_adicionar.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Adicionar Categoria
        </a>
    </div>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Listagem de categorias -->
    <div class="card">
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="datatable-header">
                            <tr>
                                <th scope="col" width="80">ID</th>
                                <th scope="col" width="80">Imagem</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Categoria Pai</th>
                                <th scope="col">Produtos</th>
                                <th scope="col">Status</th>
                                <th scope="col" width="120">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($category = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td>
                                        <?php 
                                        $image_url = !empty($category['image_url']) ? '../' . $category['image_url'] : '../assets/images/no-image.png';
                                        // Verificar se a imagem existe
                                        if (!empty($category['image_url']) && !file_exists('../' . $category['image_url'])) {
                                            $image_url = '../assets/images/no-image.png';
                                        }
                                        ?>
                                        <img src="<?php echo $image_url; ?>" 
                                            alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                            class="img-thumbnail" 
                                            style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td>
                                        <?php echo !empty($category['parent_name']) ? htmlspecialchars($category['parent_name']) : '<span class="text-muted">Nenhuma</span>'; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $category['product_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $category['status'] ? 'success' : 'danger'; ?>">
                                            <?php echo $category['status'] ? 'Ativa' : 'Inativa'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="datatable-actions">
                                            <a href="categoria_editar.php?id=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $category['id']; ?>" data-name="<?php echo htmlspecialchars($category['name']); ?>" data-count="<?php echo $category['product_count']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Nenhuma categoria encontrada.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a categoria <strong id="deleteName"></strong>?</p>
                <div id="deleteWarning" class="alert alert-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle me-2"></i> Esta categoria possui <span id="deleteCount"></span> produtos associados. Ao excluir a categoria, os produtos ficarão sem categoria.
                </div>
                <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" action="categoria_excluir.php" method="post">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar modal de exclusão
        const deleteModal = document.getElementById('deleteModal');
        let modalInstance = null;
        
        // Verificar se Bootstrap 5 está disponível
        if (typeof bootstrap !== 'undefined') {
            modalInstance = new bootstrap.Modal(deleteModal);
        }
        
        const deleteButtons = document.querySelectorAll('.btn-delete');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const count = parseInt(this.getAttribute('data-count'));
                
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteName').textContent = name;
                
                // Mostrar aviso se houver produtos associados
                if (count > 0) {
                    document.getElementById('deleteCount').textContent = count;
                    document.getElementById('deleteWarning').style.display = 'block';
                } else {
                    document.getElementById('deleteWarning').style.display = 'none';
                }
                
                // Mostrar o modal
                if (modalInstance) {
                    modalInstance.show();
                } else {
                    // Fallback se o Bootstrap não estiver disponível
                    deleteModal.style.display = 'block';
                    deleteModal.classList.add('show');
                }
            });
        });
    });
</script>

<?php
// Incluir rodapé
include 'includes/footer.php';
?>