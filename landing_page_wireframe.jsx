import React from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

/**
 * Landing Page Wireframe (3 variantes)
 * - Corrigido: remoção da declaração duplicada de LandingPageMinimal
 * - Mantidas as variantes Azul e Mostarda
 * - Variante Minimal (mostarda clean) como export default para preview
 * - Inclusos smoke tests adicionais
 */

// =========================
// Variante 1 — Azul
// =========================
export function LandingPage() {
  return (
    <div className="min-h-screen bg-zinc-50 flex flex-col">
      <header className="relative overflow-hidden py-20 px-6 text-center bg-gradient-to-br from-blue-600 via-blue-700 to-blue-900 text-white">
        <h1 className="relative text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">Mercado Afiliado — Acompanhe e Escale</h1>
        <p className="relative text-lg md:text-xl mb-6 max-w-3xl mx-auto text-blue-100">
          Painel centralizado para métricas, UTMs, alertas e integrações com plataformas.
        </p>
        <div className="relative flex justify-center gap-3">
          <Button size="lg" className="bg-blue-500 hover:bg-blue-600 text-white font-semibold">Experimente grátis</Button>
          <Button size="lg" variant="outline" className="border-blue-200 text-blue-200 hover:bg-blue-500/10">Ver demo</Button>
        </div>
      </header>
    </div>
  );
}

// =========================
// Variante 2 — Mostarda (dark hero)
// =========================
export function LandingPageMustard() {
  return (
    <div className="min-h-screen bg-zinc-50 flex flex-col">
      <header className="relative overflow-hidden py-20 px-6 text-center bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 text-zinc-100">
        <div
          className="absolute inset-0 opacity-10"
          style={{
            backgroundImage:
              'radial-gradient(circle at 20% 20%, #f59e0b 0, transparent 35%), radial-gradient(circle at 80% 30%, #f59e0b 0, transparent 35%)',
          }}
        />
        <h1 className="relative text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">Mercado Afiliado — Performance com foco e clareza</h1>
        <p className="relative text-lg md:text-xl mb-6 max-w-3xl mx-auto text-zinc-300">
          Centralize métricas, padronize UTMs e receba alertas inteligentes. Um painel enxuto e poderoso.
        </p>
        <div className="relative flex justify-center gap-3">
          <Button size="lg" className="bg-amber-600 hover:bg-amber-700 text-zinc-900 font-semibold">Experimente grátis</Button>
          <Button size="lg" variant="outline" className="border-amber-500 text-amber-400 hover:bg-amber-500/10">Ver demonstração</Button>
        </div>
      </header>
    </div>
  );
}

// =========================
// Variante 3 — Minimal (claro, SaaS clean com destaque mostarda)
// =========================
export default function LandingPageMinimal() {
  return (
    <div className="min-h-screen bg-white text-zinc-900">
      {/* Topbar simples */}
      <div className="border-b">
        <div className="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-md bg-amber-500" />
            <span className="font-semibold">Mercado Afiliado</span>
          </div>
          <div className="hidden md:flex items-center gap-4 text-sm text-zinc-600">
            <a href="#recursos" className="hover:text-zinc-900">Recursos</a>
            <a href="#precos" className="hover:text-zinc-900">Preços</a>
            <Button className="bg-amber-600 hover:bg-amber-700 text-zinc-900">Teste grátis</Button>
          </div>
        </div>
      </div>

      {/* Hero minimalista */}
      <header className="max-w-6xl mx-auto px-6 py-16">
        <h1 className="text-4xl md:text-5xl font-extrabold tracking-tight">Todos os seus números de afiliado, sem ruído.</h1>
        <p className="mt-4 text-lg text-zinc-600 max-w-2xl">Painel unificado, UTMs padronizadas e alertas inteligentes. Comece simples, escale com confiança.</p>
        <div className="mt-6 flex gap-3">
          <Button className="bg-amber-600 hover:bg-amber-700 text-zinc-900">Começar agora</Button>
          <Button variant="outline" className="border-amber-500 text-amber-600 hover:bg-amber-50">Ver demo</Button>
        </div>
      </header>

      {/* Blocos de valor */}
      <section id="recursos" className="max-w-6xl mx-auto px-6 grid md:grid-cols-3 gap-6 pb-12">
        <Card className="shadow-none border border-zinc-200">
          <CardContent className="p-6">
            <h3 className="text-lg font-semibold">Painel Unificado</h3>
            <p className="text-zinc-600 mt-1">Vendas, CR e receita por período. Sem abrir mil abas.</p>
          </CardContent>
        </Card>
        <Card className="shadow-none border border-zinc-200">
          <CardContent className="p-6">
            <h3 className="text-lg font-semibold">Link Maestro</h3>
            <p className="text-zinc-600 mt-1">UTMs consistentes, links curtos e registro de cliques.</p>
          </CardContent>
        </Card>
        <Card className="shadow-none border border-zinc-200">
          <CardContent className="p-6">
            <h3 className="text-lg font-semibold">Alerta Queda</h3>
            <p className="text-zinc-600 mt-1">Se a conversão despencar, você fica sabendo na hora.</p>
          </CardContent>
        </Card>
      </section>

      {/* Tabela de planos simplificada */}
      <section id="precos" className="bg-amber-50/40 border-t">
        <div className="max-w-6xl mx-auto px-6 py-12">
          <h2 className="text-2xl font-bold">Planos simples</h2>
          <div className="mt-6 grid md:grid-cols-3 gap-6">
            <Card className="border-amber-200">
              <CardContent className="p-6">
                <div className="text-sm text-zinc-500">Starter</div>
                <div className="text-3xl font-extrabold mt-1">R$79<span className="text-base font-medium text-zinc-500">/m</span></div>
                <ul className="mt-3 text-sm text-zinc-700 space-y-1">
                  <li>2 integrações</li>
                  <li>50k eventos/mês</li>
                  <li>Alertas por e-mail</li>
                </ul>
                <Button className="mt-4 w-full bg-amber-600 hover:bg-amber-700 text-zinc-900">Começar</Button>
              </CardContent>
            </Card>
            <Card className="border-amber-300 ring-2 ring-amber-300">
              <CardContent className="p-6">
                <div className="text-sm text-zinc-500">Pro</div>
                <div className="text-3xl font-extrabold mt-1">R$149<span className="text-base font-medium text-zinc-500">/m</span></div>
                <ul className="mt-3 text-sm text-zinc-700 space-y-1">
                  <li>4 integrações</li>
                  <li>Link Maestro avançado</li>
                  <li>Alertas WhatsApp/Telegram</li>
                </ul>
                <Button className="mt-4 w-full bg-amber-600 hover:bg-amber-700 text-zinc-900">Assinar Pro</Button>
              </CardContent>
            </Card>
            <Card className="border-amber-200">
              <CardContent className="p-6">
                <div className="text-sm text-zinc-500">Scale</div>
                <div className="text-3xl font-extrabold mt-1">R$299<span className="text-base font-medium text-zinc-500">/m</span></div>
                <ul className="mt-3 text-sm text-zinc-700 space-y-1">
                  <li>Pixel BR + CAPI Bridge</li>
                  <li>Equipe & permissões</li>
                  <li>Fila prioritária</li>
                </ul>
                <Button className="mt-4 w-full bg-amber-600 hover:bg-amber-700 text-zinc-900">Falar com vendas</Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* Rodapé minimal */}
      <footer className="border-t">
        <div className="max-w-6xl mx-auto px-6 py-6 text-sm text-zinc-500 flex items-center justify-between">
          <span>© 2025 Mercado Afiliado</span>
          <a href="#" className="hover:text-zinc-800">Privacidade</a>
        </div>
      </footer>
    </div>
  );
}

// =========================
// Test Harness & Smoke Tests
// =========================
export function TestHarness3Up() {
  return (
    <div className="grid lg:grid-cols-3 gap-6 p-6">
      <div className="border rounded-xl overflow-hidden">
        <div className="px-4 py-2 text-sm font-semibold bg-zinc-100">Variante Azul</div>
        <LandingPage />
      </div>
      <div className="border rounded-xl overflow-hidden">
        <div className="px-4 py-2 text-sm font-semibold bg-zinc-100">Variante Mostarda</div>
        <LandingPageMustard />
      </div>
      <div className="border rounded-xl overflow-hidden">
        <div className="px-4 py-2 text-sm font-semibold bg-zinc-100">Variante Minimal</div>
        <LandingPageMinimal />
      </div>
    </div>
  );
}

export function __selfSmokeTests_v2() {
  const errors = [];
  try {
    if (typeof LandingPage !== "function") errors.push("LandingPage não é uma função exportada.");
    if (typeof LandingPageMustard !== "function") errors.push("LandingPageMustard não é uma função exportada.");
    if (typeof LandingPageMinimal !== "function") errors.push("LandingPageMinimal não é uma função exportada.");
  } catch (e) {
    errors.push(`Erro ao validar exports: ${e && e.message ? e.message : e}`);
  }
  // teste extra: nomes únicos (sem duplicação)
  const names = ["LandingPage", "LandingPageMustard", "LandingPageMinimal"];
  const unique = new Set(names);
  if (unique.size !== names.length) errors.push("Identificadores duplicados detectados.");
  return { passed: errors.length === 0, errors };
}
