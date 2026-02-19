<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Plug, ExternalLink } from 'lucide-vue-next';

const props = defineProps({
    title: { type: String, default: 'Login' },
    error: { type: String, default: null },
    host: { type: String, default: 'localhost' },
    port: { type: [String, Number], default: '5432' },
    user: { type: String, default: '' },
    ssl: { type: Boolean, default: false },
});

const form = useForm({
    host: props.host,
    port: props.port,
    user: props.user,
    password: '',
    ssl: props.ssl,
});
</script>

<template>
    <GuestLayout :title="title">
        <div class="relative flex min-h-screen items-center justify-center px-4">
            <div class="absolute inset-0 bg-grid-pattern bg-grid opacity-60" />
            <div class="absolute inset-0 bg-landing-gradient" />

            <div class="relative z-10 w-full max-w-md">
                <div class="card border-term-accent/20 p-8 shadow-term-glow">
                    <div class="mb-8">
                        <p class="font-mono text-term-prompt text-sm">$ connect</p>
                        <h1 class="mt-2 font-mono text-2xl font-semibold text-term-text inline-flex items-center gap-3">
                            <img src="/pgdb.png" alt="pgdbadmin" class="h-8 w-8 shrink-0 object-contain" />
                            <span class="text-term-accent">pg</span>dbadmin
                        </h1>
                        <p class="mt-1 font-mono text-xs text-term-text-dim">PostgreSQL 18+ — host, port, user, password</p>
                        <Link :href="route('docs.connecting')" class="mt-2 inline-flex items-center gap-1 font-mono text-xs text-term-cyan hover:underline">
                            <ExternalLink class="w-3.5 h-3.5 shrink-0" />
                            Direct & SSH tunnel
                        </Link>
                    </div>
                    <div v-if="error" class="mb-4 rounded border border-term-danger/50 bg-term-danger/10 px-4 py-3 font-mono text-sm text-term-danger">
                        ERROR: {{ error }}
                    </div>
                    <form @submit.prevent="form.post(route('login'))" class="space-y-4 font-mono">
                        <div>
                            <label for="host" class="mb-1 block text-xs text-term-text-dim">host</label>
                            <input v-model="form.host" type="text" id="host" name="host" placeholder="localhost" class="input" required />
                        </div>
                        <div>
                            <label for="port" class="mb-1 block text-xs text-term-text-dim">port</label>
                            <input v-model.number="form.port" type="number" id="port" name="port" placeholder="5432" class="input" min="1" max="65535" />
                        </div>
                        <div>
                            <label for="user" class="mb-1 block text-xs text-term-text-dim">user</label>
                            <input v-model="form.user" type="text" id="user" name="user" placeholder="postgres" class="input" required />
                        </div>
                        <div>
                            <label for="password" class="mb-1 block text-xs text-term-text-dim">password</label>
                            <input v-model="form.password" type="password" id="password" name="password" placeholder="••••••••" class="input" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input v-model="form.ssl" type="checkbox" id="ssl" name="ssl" value="1" class="rounded border-term-border bg-term-bg text-term-accent focus:ring-term-accent" />
                            <label for="ssl" class="font-mono text-xs text-term-text-dim">ssl</label>
                        </div>
                        <button type="submit" class="btn-primary w-full font-mono inline-flex items-center justify-center gap-1.5" :disabled="form.processing">
                            <Plug class="w-4 h-4 shrink-0" />
                            connect
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
