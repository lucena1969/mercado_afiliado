<?php
/**
 * Gerador Dinâmico de Sitemap
 * Execute este arquivo para atualizar o sitemap.xml automaticamente
 *
 * COMO USAR:
 * 1. Via navegador: http://localhost/mercado_afiliado/generate-sitemap.php
 * 2. Via CLI: php generate-sitemap.php
 * 3. Via Cron (automático): 0 0 * * * /usr/bin/php /caminho/generate-sitemap.php
 */

// Configurações
$base_url = 'https://mercadoafiliado.com.br';
$sitemap_file = __DIR__ . '/sitemap.xml';

// Data de última modificação (hoje)
$today = date('Y-m-d');

// Estrutura de URLs do site
$urls = [
    // Homepage e páginas principais
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => $today],

    // Autenticação
    ['loc' => '/login', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/register', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],

    // Páginas institucionais (criar depois se não existirem)
    ['loc' => '/sobre', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/recursos', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['loc' => '/precos', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['loc' => '/faq', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['loc' => '/contato', 'priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/cases', 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $today],

    // Recursos/Features
    ['loc' => '/link-maestro', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['loc' => '/pixel-br', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['loc' => '/integrasync', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => $today],

    // Blog
    ['loc' => '/blog', 'priority' => '0.7', 'changefreq' => 'daily', 'lastmod' => $today],
    ['loc' => '/blog/como-rastrear-vendas-de-afiliado', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/blog/o-que-e-facebook-capi', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/blog/integracao-hotmart-pixel', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/blog/como-calcular-roi-campanhas', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/blog/pixel-conversao-lgpd', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => $today],

    // Documentação
    ['loc' => '/docs', 'priority' => '0.6', 'changefreq' => 'weekly', 'lastmod' => $today],
    ['loc' => '/docs/instalacao-pixel', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],
    ['loc' => '/docs/api', 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],

    // Políticas
    ['loc' => '/politica-privacidade', 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $today],
    ['loc' => '/termos-de-uso', 'priority' => '0.3', 'changefreq' => 'yearly', 'lastmod' => $today],
];

// Início do XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
$xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . PHP_EOL;
$xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . PHP_EOL;
$xml .= '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;
$xml .= PHP_EOL;

// Adicionar cada URL
foreach ($urls as $url) {
    $xml .= '    <url>' . PHP_EOL;
    $xml .= '        <loc>' . $base_url . $url['loc'] . '</loc>' . PHP_EOL;
    $xml .= '        <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
    $xml .= '        <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
    $xml .= '        <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
    $xml .= '    </url>' . PHP_EOL;
    $xml .= PHP_EOL;
}

// Fechar XML
$xml .= '</urlset>';

// Salvar arquivo
$result = file_put_contents($sitemap_file, $xml);

if ($result !== false) {
    echo "✅ Sitemap gerado com sucesso!" . PHP_EOL;
    echo "📍 Local: " . $sitemap_file . PHP_EOL;
    echo "📊 Total de URLs: " . count($urls) . PHP_EOL;
    echo "🕐 Atualizado em: " . date('d/m/Y H:i:s') . PHP_EOL;
    echo PHP_EOL;
    echo "🌐 URL pública: " . $base_url . "/sitemap.xml" . PHP_EOL;
} else {
    echo "❌ Erro ao gerar sitemap!" . PHP_EOL;
    echo "Verifique as permissões do diretório." . PHP_EOL;
}

// Se conectado ao banco, pode adicionar URLs dinâmicas
// Exemplo: posts de blog do banco de dados
/*
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    // Buscar posts do blog
    $stmt = $conn->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published'");
    while ($post = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Adicionar ao sitemap
    }
} catch (Exception $e) {
    echo "⚠️ Aviso: Não foi possível adicionar URLs dinâmicas do banco." . PHP_EOL;
}
*/
?>
