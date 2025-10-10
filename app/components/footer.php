<?php
/**
 * Footer Público
 * Para páginas institucionais
 */
?>
<footer style="background: #111827; color: #e5e7eb; padding: 48px 20px; margin-top: 80px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">

            <!-- Coluna 1: Logo e descrição -->
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <div style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--color-primary), #d97706); border-radius: 8px;"></div>
                    <strong style="font-size: 20px;">Mercado Afiliado</strong>
                </div>
                <p style="color: #9ca3af; line-height: 1.6; margin: 0;">
                    Plataforma completa de rastreamento e análise para afiliados digitais brasileiros.
                </p>
            </div>

            <!-- Coluna 2: Produto -->
            <div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Produto</h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 12px;"><a href="<?= BASE_URL ?>/sobre" style="color: #9ca3af; text-decoration: none; transition: color 0.2s;">Sobre</a></li>
                    <li style="margin-bottom: 12px;"><a href="<?= BASE_URL ?>/faq" style="color: #9ca3af; text-decoration: none; transition: color 0.2s;">FAQ</a></li>
                    <li style="margin-bottom: 12px;"><a href="<?= BASE_URL ?>/login" style="color: #9ca3af; text-decoration: none; transition: color 0.2s;">Login</a></li>
                    <li style="margin-bottom: 12px;"><a href="<?= BASE_URL ?>/register" style="color: #9ca3af; text-decoration: none; transition: color 0.2s;">Criar Conta</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Recursos -->
            <div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Recursos</h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 12px;"><span style="color: #9ca3af;">Link Maestro</span></li>
                    <li style="margin-bottom: 12px;"><span style="color: #9ca3af;">Pixel BR</span></li>
                    <li style="margin-bottom: 12px;"><span style="color: #9ca3af;">IntegraSync</span></li>
                    <li style="margin-bottom: 12px;"><span style="color: #9ca3af;">Painel Unificado</span></li>
                </ul>
            </div>

            <!-- Coluna 4: Contato -->
            <div>
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">Contato</h3>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="margin-bottom: 12px; color: #9ca3af;">QE 40, lote 8, sala 402</li>
                    <li style="margin-bottom: 12px; color: #9ca3af;">Brasília/DF</li>
                    <li style="margin-bottom: 12px;"><a href="tel:+5561999163260" style="color: #9ca3af; text-decoration: none;">(61) 99916-3260</a></li>
                    <li style="margin-bottom: 12px;"><a href="mailto:contato@mercadoafiliado.com.br" style="color: #9ca3af; text-decoration: none;">contato@mercadoafiliado.com.br</a></li>
                </ul>
            </div>
        </div>

        <!-- Linha divisória -->
        <div style="border-top: 1px solid #374151; padding-top: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <small style="color: #9ca3af;">
                © <?= date('Y') ?> Mercado Afiliado Serviços Online Ltda. Todos os direitos reservados.
            </small>
            <div style="display: flex; gap: 24px;">
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 14px; transition: color 0.2s;">Privacidade</a>
                <a href="#" style="color: #9ca3af; text-decoration: none; font-size: 14px; transition: color 0.2s;">Termos</a>
            </div>
        </div>
    </div>
</footer>

<style>
    footer a:hover {
        color: var(--color-primary, #f59e0b) !important;
    }
</style>
