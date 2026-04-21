<template>
    <div class="guest-wrapper">
        <div class="guest-decoration" aria-hidden="true"></div>

        <div class="guest-content">
            <div class="brand">
                <div class="brand-mark">
                    <Moon :size="20" :stroke-width="2" />
                </div>
                <div class="brand-text">
                    <div class="brand-name">LUA</div>
                    <div class="brand-tagline">Sistema de gestão · PetShop</div>
                </div>
            </div>

            <div class="auth-card">
                <router-view />
            </div>

            <div class="guest-footer">
                &copy; {{ new Date().getFullYear() }} LUA · Gestão para petshops
            </div>
        </div>
    </div>
</template>

<script setup>
import { Moon } from 'lucide-vue-next';
</script>

<style scoped>
.guest-wrapper {
    min-height: 100vh;
    background: var(--lua-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    position: relative;
    overflow: hidden;
}

.guest-decoration {
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 20%, rgba(110, 86, 207, 0.06), transparent 45%),
        radial-gradient(circle at 80% 75%, rgba(110, 86, 207, 0.045), transparent 40%);
    pointer-events: none;
}

.guest-wrapper::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(to right, var(--lua-border) 1px, transparent 1px),
        linear-gradient(to bottom, var(--lua-border) 1px, transparent 1px);
    background-size: 56px 56px;
    opacity: 0.35;
    mask-image: radial-gradient(ellipse at center, black 0%, transparent 70%);
    -webkit-mask-image: radial-gradient(ellipse at center, black 0%, transparent 70%);
    pointer-events: none;
}

.guest-content {
    position: relative;
    width: 100%;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.75rem;
}

.brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.brand-mark {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--lua-primary);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.brand-text {
    text-align: left;
}

.brand-name {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 1.125rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    color: var(--lua-text);
    line-height: 1;
}

.brand-tagline {
    font-size: 0.75rem;
    color: var(--lua-text-muted);
    margin-top: 0.2rem;
    letter-spacing: -0.005em;
}

.auth-card {
    width: 100%;
    background: var(--lua-surface);
    border: 1px solid var(--lua-border);
    border-radius: 14px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 24px rgba(0, 0, 0, 0.04);
}

.guest-footer {
    font-size: 0.75rem;
    color: var(--lua-text-subtle);
    letter-spacing: -0.005em;
}

@media (max-width: 480px) {
    .auth-card { padding: 1.5rem; }
}
</style>
