<?php
/**
 * Componente Header Unificado
 * Mercado Afiliado
 */

// Garantir que temos dados do usuário
if (!isset($user_data) && isset($_SESSION['user'])) {
    $user_data = $_SESSION['user'];
}

$user_name = $user_data['name'] ?? 'Usuário';
$user_initials = '';

// Gerar iniciais do nome
if ($user_name) {
    $name_parts = explode(' ', trim($user_name));
    foreach ($name_parts as $part) {
        if (!empty($part)) {
            $user_initials .= strtoupper(substr($part, 0, 1));
        }
    }
    $user_initials = substr($user_initials, 0, 2); // Máximo 2 letras
}

if (empty($user_initials)) {
    $user_initials = 'U';
}
?>

<header class="app-header">
    <a href="<?= BASE_URL ?>/dashboard" class="app-header-logo">
        <div class="logo-icon">M</div>
        Mercado Afiliado
    </a>

    <div class="app-header-actions">
        <div class="dropdown">
            <div class="app-header-user" onclick="toggleUserDropdown()">
                <div class="user-avatar"><?= $user_initials ?></div>
                <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                <i data-lucide="chevron-down" style="width: 16px; height: 16px;"></i>
            </div>
            <div class="dropdown-content">
                <a href="<?= BASE_URL ?>/dashboard" class="dropdown-item">
                    <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                    Meu Perfil
                </a>
                <a href="<?= BASE_URL ?>/subscribe" class="dropdown-item">
                    <i data-lucide="credit-card" style="width: 16px; height: 16px;"></i>
                    Assinatura
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= BASE_URL ?>/logout" class="dropdown-item">
                    <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                    Sair
                </a>
            </div>
        </div>
    </div>
</header>

<script>
// Função para controlar dropdown
function toggleUserDropdown() {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Fechar dropdown quando clicar fora
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});
</script>