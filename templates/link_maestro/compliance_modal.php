<?php
/**
 * Modal de Termo de Compromisso - Link Maestro
 * Exibido antes de criar o primeiro link do usuário
 */
?>

<!-- Modal de Compliance e Termo de Compromisso -->
<div id="complianceModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px; max-height: 90vh;">
        <div class="modal-header">
            <h2 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                <span>🛡️</span> Termo de Compromisso - Link Maestro
            </h2>
            <span class="modal-close" onclick="closeComplianceModal()">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Introdução -->
            <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 4px solid #3b82f6; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1rem 0; color: #1e40af; display: flex; align-items: center; gap: 0.5rem;">
                    <span>⚡</span> Bem-vindo ao Link Maestro!
                </h3>
                <p style="margin: 0; line-height: 1.6; color: #1e3a8a;">
                    Para garantir a <strong>conformidade com as políticas do Google, Facebook e outras plataformas</strong>, 
                    precisamos que você esteja ciente das boas práticas e se comprometa a usar nossa ferramenta de forma ética e transparente.
                </p>
            </div>

            <!-- Seções do Termo -->
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 1.5rem; margin-bottom: 2rem;">
                
                <!-- 1. Uso Permitido -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: #10b981; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>✅</span> 1. Uso Permitido e Encorajado
                    </h4>
                    <ul style="line-height: 1.6; color: #374151; margin-left: 1rem;">
                        <li><strong>Marketing legítimo:</strong> Promoção de produtos/serviços reais e legais</li>
                        <li><strong>E-commerce:</strong> Links para lojas virtuais, produtos específicos</li>
                        <li><strong>Conteúdo educativo:</strong> Cursos, ebooks, webinars, artigos</li>
                        <li><strong>Páginas de captura:</strong> Formulários, newsletters, landing pages</li>
                        <li><strong>Redes sociais:</strong> YouTube, Instagram, LinkedIn, blogs</li>
                        <li><strong>Afiliação transparente:</strong> Hotmart, Monetizze, Amazon Afiliados</li>
                    </ul>
                </div>

                <!-- 2. Uso Proibido -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: #ef4444; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>❌</span> 2. Uso Estritamente Proibido
                    </h4>
                    <ul style="line-height: 1.6; color: #374151; margin-left: 1rem;">
                        <li><strong>Conteúdo malicioso:</strong> Malware, phishing, vírus</li>
                        <li><strong>Ofertas enganosas:</strong> "Fique rico rápido", promessas falsas</li>
                        <li><strong>Conteúdo adulto:</strong> Sem declaração adequada</li>
                        <li><strong>Produtos ilegais:</strong> Drogas, armas, documentos falsos</li>
                        <li><strong>Spam:</strong> Envio em massa não solicitado</li>
                        <li><strong>Violação de direitos:</strong> Conteúdo pirata, marcas registradas</li>
                        <li><strong>Cloaking:</strong> Ocultar destino real do link</li>
                    </ul>
                </div>

                <!-- 3. Conformidade com Plataformas -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: #f59e0b; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🔗</span> 3. Conformidade com Google e Facebook
                    </h4>
                    <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 4px; padding: 1rem; margin-bottom: 1rem;">
                        <p style="margin: 0; font-size: 0.875rem; color: #92400e;">
                            <strong>⚠️ IMPORTANTE:</strong> O Link Maestro gera links transparentes que são totalmente compatíveis 
                            com as políticas do Google Ads e Facebook Ads, mas o <strong>conteúdo de destino é sua responsabilidade</strong>.
                        </p>
                    </div>
                    <ul style="line-height: 1.6; color: #374151; margin-left: 1rem;">
                        <li>Seus links devem redirecionar para o conteúdo exato anunciado</li>
                        <li>Não oculte ou mascare o verdadeiro destino</li>
                        <li>Respeite as políticas de cada plataforma onde promover</li>
                        <li>Mantenha seus links sempre funcionais e atualizados</li>
                    </ul>
                </div>

                <!-- 4. Monitoramento -->
                <div style="margin-bottom: 2rem;">
                    <h4 style="color: #6366f1; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📊</span> 4. Monitoramento e Auditoria
                    </h4>
                    <ul style="line-height: 1.6; color: #374151; margin-left: 1rem;">
                        <li>Todos os links são registrados e auditáveis</li>
                        <li>Implementamos validações automáticas de segurança</li>
                        <li>Links reportados como problemáticos são investigados</li>
                        <li>Reservamos o direito de suspender links que violem termos</li>
                    </ul>
                </div>

                <!-- 5. Responsabilidades -->
                <div style="margin-bottom: 1rem;">
                    <h4 style="color: #8b5cf6; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>⚖️</span> 5. Suas Responsabilidades
                    </h4>
                    <ul style="line-height: 1.6; color: #374151; margin-left: 1rem;">
                        <li>Verificar se o destino está funcionando antes de usar</li>
                        <li>Não criar links para conteúdo que você não controla/conhece</li>
                        <li>Informar imediatamente sobre problemas ou abusos</li>
                        <li>Manter-se atualizado sobre políticas das plataformas</li>
                        <li>Usar apenas para fins comerciais legítimos</li>
                    </ul>
                </div>
            </div>

            <!-- Alertas Educativos -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                <div style="background: #f0fdf4; border: 1px solid #10b981; border-radius: 6px; padding: 1rem;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #065f46; display: flex; align-items: center; gap: 0.5rem;">
                        <span>💡</span> Dica Profissional
                    </h5>
                    <p style="margin: 0; font-size: 0.875rem; color: #065f46;">
                        Use títulos descritivos e organizados para seus links. Isso facilita o gerenciamento e demonstra profissionalismo.
                    </p>
                </div>
                <div style="background: #eff6ff; border: 1px solid #3b82f6; border-radius: 6px; padding: 1rem;">
                    <h5 style="margin: 0 0 0.5rem 0; color: #1e40af; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🔍</span> Transparência
                    </h5>
                    <p style="margin: 0; font-size: 0.875rem; color: #1e40af;">
                        Nossos links são totalmente transparentes. Qualquer pessoa pode ver o destino final antes de clicar.
                    </p>
                </div>
            </div>

            <!-- Checkbox de Concordância -->
            <div style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
                <label style="display: flex; align-items: flex-start; gap: 1rem; cursor: pointer;" for="agreeCompliance">
                    <input type="checkbox" id="agreeCompliance" required style="margin-top: 0.25rem; transform: scale(1.2);">
                    <div>
                        <div style="font-weight: 600; color: #111827; margin-bottom: 0.5rem;">
                            ✍️ Declaro que li, compreendi e concordo com todos os termos acima
                        </div>
                        <div style="font-size: 0.875rem; color: #6b7280; line-height: 1.5;">
                            Ao marcar esta opção, você se compromete a usar o Link Maestro de forma ética, 
                            respeitando todas as políticas mencionadas e assumindo total responsabilidade 
                            pelo conteúdo dos links que criar.
                        </div>
                    </div>
                </label>
            </div>

            <!-- Botões -->
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                <div style="font-size: 0.875rem; color: #6b7280;">
                    Este termo será exibido apenas uma vez por usuário
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="button" onclick="closeComplianceModal()" class="btn" style="background: #e5e7eb;">
                        ❌ Não Concordo
                    </button>
                    <button type="button" id="acceptComplianceBtn" onclick="acceptCompliance()" class="btn btn-primary" disabled>
                        ✅ Concordo e Prosseguir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Compliance Modal Functions
let complianceAccepted = localStorage.getItem('linkMaestroComplianceAccepted') === 'true';

function showComplianceModal() {
    if (complianceAccepted) {
        return false; // Não mostrar se já foi aceito
    }
    
    document.getElementById('complianceModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    return true; // Modal foi exibido
}

function closeComplianceModal() {
    document.getElementById('complianceModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function acceptCompliance() {
    // Salvar aceitação
    localStorage.setItem('linkMaestroComplianceAccepted', 'true');
    localStorage.setItem('linkMaestroComplianceDate', new Date().toISOString());
    
    // Registrar no servidor
    fetch('<?= BASE_URL ?>/api/link-maestro/compliance', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'accept_compliance',
            timestamp: new Date().toISOString(),
            version: '1.0'
        })
    });
    
    complianceAccepted = true;
    closeComplianceModal();
    
    // Agora pode abrir o modal de criação
    openCreateLinkModal();
}

// Habilitar botão apenas quando checkbox marcado
document.getElementById('agreeCompliance').addEventListener('change', function() {
    document.getElementById('acceptComplianceBtn').disabled = !this.checked;
});

// Fechar modal clicando fora (mas não para compliance)
document.getElementById('complianceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        // Para compliance modal, não fechar automaticamente
        // Usuário deve explicitamente escolher
    }
});
</script>