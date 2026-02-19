<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, LogIn } from 'lucide-vue-next';
</script>

<template>
    <GuestLayout title="Verbinden met PostgreSQL">
        <div class="relative min-h-screen">
            <div class="absolute inset-0 bg-grid-pattern bg-grid opacity-50" />
            <div class="relative z-10 mx-auto max-w-3xl px-4 py-10">
                <div class="mb-8 flex items-center justify-between">
                    <Link :href="route('login')" class="font-mono text-sm text-term-text-dim hover:text-term-accent inline-flex items-center gap-1.5">
                        <ArrowLeft class="w-4 h-4 shrink-0" />
                        back to login
                    </Link>
                    <img src="/pgdb.png" alt="pgdbadmin" class="h-6 w-6 shrink-0 object-contain" />
                </div>
                <div class="card border-term-border p-8">
                    <p class="font-mono text-xs text-term-prompt">$ man connecting</p>
                    <h1 class="mt-2 font-mono text-2xl font-semibold text-term-text inline-flex items-center gap-2">
                        <BookOpen class="w-6 h-6 text-term-amber shrink-0" />
                        Verbinden met PostgreSQL — Direct & via SSH
                    </h1>
                    <p class="mt-3 font-mono text-sm text-term-text-dim leading-relaxed">
                        PG Admin werkt met <span class="text-term-text">elke</span> PostgreSQL-database: lokaal, op een server, in de cloud (AWS RDS, DigitalOcean, etc.) of achter een firewall.
                    </p>

                    <h2 class="mt-8 font-mono text-lg font-medium text-term-amber">1. Directe verbinding</h2>
                    <p class="mt-2 font-mono text-sm text-term-text-dim">
                        Gebruik wanneer je hostnaam of IP en poort kent, zonder firewall ertussen.
                    </p>
                    <div class="table-container mt-4">
                        <table class="data-table">
                            <thead>
                                <tr><th>veld</th><th>beschrijving</th></tr>
                            </thead>
                            <tbody>
                                <tr><td class="font-medium text-term-text">Host</td><td class="font-mono text-term-text-dim">Hostnaam of IP (<code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">localhost</code>, <code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">db.example.com</code>)</td></tr>
                                <tr><td class="font-medium text-term-text">Port</td><td class="font-mono text-term-text-dim">Meestal <code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">5432</code></td></tr>
                                <tr><td class="font-medium text-term-text">User</td><td class="font-mono text-term-text-dim">PostgreSQL-gebruiker</td></tr>
                                <tr><td class="font-medium text-term-text">Password</td><td class="font-mono text-term-text-dim">Wachtwoord</td></tr>
                                <tr><td class="font-medium text-term-text">SSL</td><td class="font-mono text-term-text-dim">Aanvinken als de server SSL vereist</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 font-mono text-xs text-term-text-dim">
                        De server waar PG Admin draait moet de database kunnen bereiken. Bij cloud: controleer "Trusted sources" / "Allowed IPs".
                    </p>

                    <h2 class="mt-8 font-mono text-lg font-medium text-term-amber">2. Verbinding via SSH-tunnel</h2>
                    <p class="mt-2 font-mono text-sm text-term-text-dim">
                        Als de database alleen via een jump host bereikbaar is, maak eerst een SSH-tunnel; verbind dan in PG Admin naar <code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">localhost</code> op de tunnelpoort.
                    </p>

                    <h3 class="mt-6 font-mono text-base font-medium text-term-text">Stap 1: SSH-key</h3>
                    <pre class="mt-2 overflow-x-auto rounded border border-term-border bg-term-bg p-4 font-mono text-sm text-term-text-dim"><code>ssh-keygen -t ed25519 -C "jouw@email" -f ~/.ssh/pgadmin_jump</code></pre>
                    <p class="mt-2 font-mono text-xs text-term-text-dim">Publieke key op jump host:</p>
                    <pre class="mt-1 overflow-x-auto rounded border border-term-border bg-term-bg p-4 font-mono text-sm text-term-text-dim"><code>ssh-copy-id -i ~/.ssh/pgadmin_jump.pub user@jump-host.example.com</code></pre>

                    <h3 class="mt-6 font-mono text-base font-medium text-term-text">Stap 2: SSH-tunnel</h3>
                    <pre class="mt-2 overflow-x-auto rounded border border-term-border bg-term-bg p-4 font-mono text-sm text-term-text-dim"><code>ssh -i ~/.ssh/pgadmin_jump -L 5433:db-host-intern:5432 user@jump-host.example.com -N</code></pre>
                    <ul class="mt-3 list-inside list-disc space-y-1 font-mono text-xs text-term-text-dim">
                        <li><code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">-L 5433:db-host-intern:5432</code> — lokaal 5433 → DB</li>
                        <li><code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">-N</code> — alleen tunnel</li>
                    </ul>
                    <p class="mt-2 font-mono text-xs text-term-text-dim">Laat het terminalvenster open. <code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">db-host-intern</code> = hostnaam/IP van PostgreSQL zoals de jump host die ziet.</p>

                    <h3 class="mt-6 font-mono text-base font-medium text-term-text">Stap 3: In PG Admin</h3>
                    <p class="mt-2 font-mono text-sm text-term-text-dim">Vul in op het inlogscherm:</p>
                    <ul class="mt-2 list-inside list-disc space-y-1 font-mono text-sm text-term-text-dim">
                        <li><span class="text-term-text">Host:</span> <code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">localhost</code></li>
                        <li><span class="text-term-text">Port:</span> <code class="rounded border border-term-border bg-term-bg px-1 text-term-accent">5433</code></li>
                        <li><span class="text-term-text">User / Password:</span> PostgreSQL-gebruiker</li>
                        <li><span class="text-term-text">SSL:</span> meestal uit</li>
                    </ul>

                    <h2 class="mt-8 font-mono text-lg font-medium text-term-amber">Waar draait PG Admin?</h2>
                    <ul class="mt-3 space-y-2 font-mono text-sm text-term-text-dim">
                        <li><span class="text-term-text">Eigen computer:</span> Start de SSH-tunnel op dezelfde machine; in PG Admin: host localhost, tunnelpoort.</li>
                        <li><span class="text-term-text">Op een server:</span> SSH naar de server, start daar de tunnel; verbind in PG Admin op die server naar localhost:5433.</li>
                    </ul>

                    <div class="mt-8 rounded border border-term-accent/30 bg-term-accent/10 p-4">
                        <h3 class="font-mono font-medium text-term-accent">Samenvatting</h3>
                        <div class="table-container mt-2">
                            <table class="data-table w-full">
                                <tbody>
                                    <tr><td class="font-medium text-term-text">Database direct bereikbaar</td><td class="text-term-text-dim">Direct: host, port, user, password, eventueel SSL.</td></tr>
                                    <tr><td class="font-medium text-term-text">Alleen via jump host</td><td class="text-term-text-dim">SSH-tunnel (<code class="rounded border border-term-border bg-term-bg px-1 text-xs">ssh -L ...</code>), daarna PG Admin: host localhost, port = tunnelpoort.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-8 border-t border-term-border pt-6">
                        <Link :href="route('login')" class="btn-primary inline-flex font-mono items-center gap-1.5">
                            <LogIn class="w-4 h-4 shrink-0" />
                            naar inloggen
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
