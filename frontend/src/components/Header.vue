<template>
  <nav class="navbar is-dark is-fixed-top" role="navigation" aria-label="main navigation">
    <div class="container">
      <div class="navbar-brand">
        <router-link to="/" class="navbar-item">
          <span class="icon-text">
            <span class="icon has-text-danger">
              <i class="fas fa-film"></i>
            </span>
            <span class="has-text-weight-bold is-size-4">CineRadar</span>
          </span>
        </router-link>

        <a role="button" class="navbar-burger" :class="{ 'is-active': isMenuActive }" @click="toggleMenu" aria-label="menu" aria-expanded="false">
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
          <span aria-hidden="true"></span>
        </a>
      </div>

      <div class="navbar-menu" :class="{ 'is-active': isMenuActive }">
        <div class="navbar-start">
          <router-link to="/" class="navbar-item" @click="closeMenu">
            <span class="icon">
              <i class="fas fa-home"></i>
            </span>
            <span>Home</span>
          </router-link>

          <router-link to="/estreias" class="navbar-item" @click="closeMenu">
            <span class="icon">
              <i class="fas fa-star"></i>
            </span>
            <span>Próximas Estreias</span>
          </router-link>

          <router-link to="/em-cartaz" class="navbar-item" @click="closeMenu">
            <span class="icon">
              <i class="fas fa-ticket-alt"></i>
            </span>
            <span>Em Cartaz</span>
          </router-link>

          <router-link to="/lancamentos" class="navbar-item" @click="closeMenu">
            <span class="icon">
              <i class="fas fa-calendar-alt"></i>
            </span>
            <span>Lançamentos</span>
          </router-link>

          <router-link to="/sobre" class="navbar-item" @click="closeMenu">
            <span class="icon">
              <i class="fas fa-info-circle"></i>
            </span>
            <span>Sobre</span>
          </router-link>
        </div>

        <div class="navbar-end">
          <div class="navbar-item">
            <div class="field has-addons">
              <p class="control has-icons-left">
                <input 
                  class="input is-rounded" 
                  type="search" 
                  placeholder="Buscar filmes..." 
                  v-model="searchQuery" 
                  @keyup.enter="searchMovies"
                >
                <span class="icon is-left">
                  <i class="fas fa-search"></i>
                </span>
              </p>
              <p class="control">
                <button class="button is-primary is-rounded" @click="searchMovies">
                  <span class="icon">
                    <i class="fas fa-search"></i>
                  </span>
                </button>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
  <!-- Spacer for fixed navbar -->
  <div style="height: 52px;"></div>
</template>

<script>
import { useRouter } from 'vue-router'

export default {
  name: 'Header',
  setup() {
    const router = useRouter()

    return {
      router
    }
  },
  data() {
    return {
      isMenuActive: false,
      searchQuery: ''
    }
  },
  methods: {
    toggleMenu() {
      this.isMenuActive = !this.isMenuActive
    },
    closeMenu() {
      this.isMenuActive = false
    },
    searchMovies() {
      if (this.searchQuery.trim()) {
        this.router.push({ path: '/buscar', query: { q: this.searchQuery.trim() } })
        this.closeMenu()
        this.searchQuery = ''
      }
    }
  }
}
</script>

<style scoped>
.navbar {
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

.navbar-item.router-link-active {
  background-color: rgba(229, 9, 20, 0.1);
  color: #e50914 !important;
}

.navbar-burger {
  color: #fff;
}

@media (max-width: 1023px) {
  .navbar-menu {
    background-color: #141414;
  }
}
</style>