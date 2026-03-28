<template>
    <div class="guest-wrapper">
        <div class="auth-card">
            <div class="brand">
                <h1><i class="bi bi-heart-pulse-fill"></i> LUA</h1>
                <p>Sistema de Gestao - PetShop</p>
            </div>
            <router-view />
        </div>
    </div>
</template>

<style scoped>
.guest-wrapper {
    background: linear-gradient(135deg, #2c1e4a 0%, #6f42c1 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.auth-card {
    background: var(--lua-card-bg, #fff);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 2.5rem;
    width: 100%;
    max-width: 420px;
}
.brand {
    text-align: center;
    margin-bottom: 2rem;
}
.brand h1 {
    color: var(--lua-primary, #6f42c1);
    font-weight: 700;
    font-size: 1.8rem;
}
.brand p {
    color: var(--lua-text-muted, #6b7280);
    font-size: 0.9rem;
}
</style>
