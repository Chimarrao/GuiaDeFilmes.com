<template>
  <nav class="navbar is-dark is-fixed-top" role="navigation" aria-label="main navigation">
    <div class="container">
      <div class="navbar-brand">
        <router-link to="/" class="navbar-item">
          <span class="icon-text">
            <span class="icon has-text-danger">
              <i class="fas fa-film"></i>
            </span>
            <span class="has-text-weight-bold is-size-4">Guia de Filmes</span>
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

          <router-link to="/explorar" class="navbar-item" @click="closeMenu">
            <span class="icon">
              <i class="fas fa-compass"></i>
            </span>
            <span>Explorar</span>
          </router-link>

          <!-- Submenu Gênero -->
          <div class="navbar-item has-dropdown is-hoverable">
            <a class="navbar-link">
              <span class="icon">
                <i class="fas fa-masks-theater"></i>
              </span>
              <span>Gênero</span>
            </a>
            <div class="navbar-dropdown">
              <router-link to="/explorar/genero/acao" class="navbar-item" @click="closeMenu">Ação</router-link>
              <router-link to="/explorar/genero/aventura" class="navbar-item" @click="closeMenu">Aventura</router-link>
              <router-link to="/explorar/genero/comedia" class="navbar-item" @click="closeMenu">Comédia</router-link>
              <router-link to="/explorar/genero/drama" class="navbar-item" @click="closeMenu">Drama</router-link>
              <router-link to="/explorar/genero/ficcao-cientifica" class="navbar-item" @click="closeMenu">Ficção Científica</router-link>
              <router-link to="/explorar/genero/terror" class="navbar-item" @click="closeMenu">Terror</router-link>
              <router-link to="/explorar/genero/romance" class="navbar-item" @click="closeMenu">Romance</router-link>
              <router-link to="/explorar/genero/suspense" class="navbar-item" @click="closeMenu">Suspense</router-link>
              <router-link to="/explorar/genero/animacao" class="navbar-item" @click="closeMenu">Animação</router-link>
              <router-link to="/explorar/genero/fantasia" class="navbar-item" @click="closeMenu">Fantasia</router-link>
            </div>
          </div>

          <!-- Submenu Nacionalidade -->
          <div class="navbar-item has-dropdown is-hoverable">
            <a class="navbar-link">
              <span class="icon">
                <i class="fas fa-globe"></i>
              </span>
              <span>Nacionalidade</span>
            </a>
            <div class="navbar-dropdown">
              <router-link to="/explorar/pais/BR" class="navbar-item" @click="closeMenu">🇧🇷 Brasil</router-link>
              <router-link to="/explorar/pais/US" class="navbar-item" @click="closeMenu">🇺🇸 Estados Unidos</router-link>
              <router-link to="/explorar/pais/GB" class="navbar-item" @click="closeMenu">�� Reino Unido</router-link>
              <router-link to="/explorar/pais/FR" class="navbar-item" @click="closeMenu">🇫🇷 França</router-link>
              <router-link to="/explorar/pais/DE" class="navbar-item" @click="closeMenu">�� Alemanha</router-link>
              <router-link to="/explorar/pais/IT" class="navbar-item" @click="closeMenu">🇮🇹 Itália</router-link>
              <router-link to="/explorar/pais/ES" class="navbar-item" @click="closeMenu">🇪🇸 Espanha</router-link>
              <router-link to="/explorar/pais/JP" class="navbar-item" @click="closeMenu">🇯🇵 Japão</router-link>
              <router-link to="/explorar/pais/KR" class="navbar-item" @click="closeMenu">�🇷 Coreia do Sul</router-link>
              <router-link to="/explorar/pais/IN" class="navbar-item" @click="closeMenu">🇮🇳 Índia</router-link>
            </div>
          </div>

          <!-- Submenu Década -->
          <div class="navbar-item has-dropdown is-hoverable">
            <a class="navbar-link">
              <span class="icon">
                <i class="fas fa-calendar-alt"></i>
              </span>
              <span>Década</span>
            </a>
            <div class="navbar-dropdown">
              <router-link to="/explorar/decada/2020s" class="navbar-item" @click="closeMenu">Anos 2020</router-link>
              <router-link to="/explorar/decada/2010s" class="navbar-item" @click="closeMenu">Anos 2010</router-link>
              <router-link to="/explorar/decada/2000s" class="navbar-item" @click="closeMenu">Anos 2000</router-link>
              <router-link to="/explorar/decada/1990s" class="navbar-item" @click="closeMenu">Anos 1990</router-link>
              <router-link to="/explorar/decada/1980s" class="navbar-item" @click="closeMenu">Anos 1980</router-link>
              <router-link to="/explorar/decada/1970s" class="navbar-item" @click="closeMenu">Anos 1970</router-link>
            </div>
          </div>

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
      isDropdownActive: false,
      searchQuery: ''
    }
  },
  methods: {
    toggleMenu() {
      this.isMenuActive = !this.isMenuActive
      if (!this.isMenuActive) {
        this.isDropdownActive = false
      }
    },
    toggleDropdown() {
      this.isDropdownActive = !this.isDropdownActive
    },
    closeMenu() {
      this.isMenuActive = false
      this.isDropdownActive = false
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

.navbar-dropdown {
  background-color: #0a0a0a !important;
  border-top: 2px solid #e50914;
  border-left: 1px solid rgba(229, 9, 20, 0.3);
  border-right: 1px solid rgba(229, 9, 20, 0.3);
  border-bottom: 1px solid rgba(229, 9, 20, 0.3);
}

.navbar-dropdown .navbar-item {
  color: #fff !important;
  background-color: transparent;
}

.navbar-dropdown .navbar-item:hover {
  background-color: rgba(229, 9, 20, 0.2) !important;
  color: #e50914 !important;
}

.navbar-dropdown .heading {
  color: #e50914 !important;
  font-weight: bold;
  text-transform: uppercase;
  font-size: 0.75rem;
  padding: 0.5rem 1rem;
}

.navbar-divider {
  background-color: rgba(229, 9, 20, 0.2);
}

.navbar-link {
  color: #fff !important;
}

.navbar-link:hover {
  background-color: rgba(229, 9, 20, 0.1);
  color: #e50914 !important;
}

.navbar-item {
  background-color: rgba(229, 9, 20, 0.1) !important;
}

@media (max-width: 1023px) {
  .navbar-menu {
    background-color: #141414;
  }
  
  .navbar-dropdown {
    background-color: #000000 !important;
    padding-left: 1rem;
    border-left: none;
    border-right: none;
  }
  
  .navbar-dropdown .navbar-item {
    padding-left: 2rem;
    background-color: transparent !important;
  }
  
  .navbar-dropdown .navbar-item:hover {
    background-color: rgba(229, 9, 20, 0.15) !important;
  }
  
  .has-dropdown.is-active .navbar-dropdown {
    display: block !important;
  }
}
</style>