<template>
  <nav class="gifts-nav" aria-label="Gifts">
    <RouterLink replace :to="{ name: 'gifts' }" exact-active-class="active">My list</RouterLink>
    <RouterLink replace :to="{ name: 'gifts-people' }" active-class="active">People</RouterLink>
  </nav>
</template>

<style scoped>
.gifts-nav {
  display: flex;
  gap: 0.25rem;
  padding: 0.2rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
}

.gifts-nav a {
  flex: 1;
  padding: 0.45rem;
  text-align: center;
  color: var(--text);
  text-decoration: none;
  font-weight: 500;
  border-radius: 8px;
}

.gifts-nav a.active {
  color: var(--on-accent);
  background: var(--accent);
  font-weight: 600;
}
</style>
