<?php
/**
 * Redirecionamento para a página de registro através do router
 */
header('Location: ' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/register');
exit;
?>