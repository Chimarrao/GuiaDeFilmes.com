<template>
  <div class="country-movies">
    <section class="hero is-medium is-dark">
      <div class="hero-background" :style="{ backgroundImage: `url(https://flagcdn.com/w640/${getCountryInfo().code.toLowerCase()}.png)` }"></div>
      <div class="hero-body">
        <div class="container">
          <h1 class="title is-1">
            <span class="icon-text">
              <img :src="getCountryInfo().flag" :alt="`Bandeira ${getCountryInfo().name}`" class="country-flag-title" />
              <span>Filmes {{ getCountryInfo().name }}</span>
            </span>
          </h1>
          <p class="subtitle">{{ getCountryDescription() }}</p>
        </div>
      </div>
    </section>

    <!-- Filtros Avançados -->
    <section class="section">
      <div class="container">
        <div class="box" style="background-color: var(--background-card);">
          <h2 class="title is-4 has-text-white mb-4">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-filter"></i>
              </span>
              <span>Filtros Avançados</span>
            </span>
          </h2>
          
          <div class="columns is-multiline">
            <div class="column is-3">
              <div class="field">
                <label class="label has-text-white">Gênero</label>
                <div class="control">
                  <div class="select is-fullwidth">
                    <select v-model="filters.genre">
                      <option value="">Todos</option>
                      <option value="acao">Ação</option>
                      <option value="aventura">Aventura</option>
                      <option value="comedia">Comédia</option>
                      <option value="drama">Drama</option>
                      <option value="terror">Terror</option>
                      <option value="ficcao-cientifica">Ficção Científica</option>
                      <option value="romance">Romance</option>
                      <option value="suspense">Suspense</option>
                      <option value="animacao">Animação</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="column is-3">
              <div class="field">
                <label class="label has-text-white">Ano Inicial</label>
                <div class="control">
                  <input class="input" type="number" v-model="filters.yearFrom" placeholder="Ex: 2000" min="1900" :max="currentYear">
                </div>
              </div>
            </div>

            <div class="column is-3">
              <div class="field">
                <label class="label has-text-white">Ano Final</label>
                <div class="control">
                  <input class="input" type="number" v-model="filters.yearTo" placeholder="Ex: 2024" min="1900" :max="currentYear">
                </div>
              </div>
            </div>

            <div class="column is-3">
              <div class="field">
                <label class="label has-text-white">Nota Mínima</label>
                <div class="control">
                  <div class="select is-fullwidth">
                    <select v-model="filters.minRating">
                      <option value="">Todas</option>
                      <option value="5">5+</option>
                      <option value="6">6+</option>
                      <option value="7">7+</option>
                      <option value="8">8+</option>
                      <option value="9">9+</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="column is-12">
              <div class="buttons">
                <button class="button is-primary" @click="applyFilters">
                  <span class="icon">
                    <i class="fas fa-search"></i>
                  </span>
                  <span>Aplicar Filtros</span>
                </button>
                <button class="button is-light" @click="clearFilters">
                  <span class="icon">
                    <i class="fas fa-times"></i>
                  </span>
                  <span>Limpar</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Movies Grid -->
    <section class="section">
      <div class="container">
        <div v-if="loading" class="has-text-centered">
          <div class="spinner"></div>
        </div>

        <div v-else-if="movies.length > 0">
          <div class="columns is-multiline is-mobile">
            <div v-for="movie in movies" :key="movie.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
              <MovieCard :movie="movie" />
            </div>
          </div>

          <!-- Loading More -->
          <div v-if="isLoadingMore" class="has-text-centered py-5">
            <div class="spinner"></div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>Nenhum filme encontrado</p>
        </div>

        <!-- Pagination (outside conditional blocks) -->
        <nav v-if="!loading && totalPages > 1" class="pagination is-centered mt-6" role="navigation" aria-label="pagination">
          <button 
            class="pagination-previous" 
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
          >
            Anterior
          </button>
          <button 
            class="pagination-next" 
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
          >
            Próxima
          </button>
          <ul class="pagination-list">
            <li v-for="page in getPageNumbers()" :key="page">
              <span v-if="page === '...'" class="pagination-ellipsis">&hellip;</span>
              <button 
                v-else
                class="pagination-link" 
                :class="{ 'is-current': page === currentPage }"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'
import { resolveCountry } from '../utils/countryFlags.js'
import axios from 'axios'

/**
 * Componente CountryMovies
 * 
 * Exibe filmes filtrados por país de produção com filtros avançados
 * e paginação. Permite filtrar por gênero, ano e nota mínima.
 * 
 * @component
 */
export default {
  name: 'CountryMovies',
  components: {
    MovieCard
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const loading = ref(true)
    const isLoadingMore = ref(false)
    const movies = ref([])
    const currentPage = ref(1)
    const totalPages = ref(1)
    const currentYear = new Date().getFullYear()
    const filters = ref({
      genre: '',
      yearFrom: '',
      yearTo: '',
      minRating: ''
    })

    /**
     * Obtém informações do país a partir do código na URL
     * Usa resolveCountry para suportar países modernos e históricos
     * 
     * @returns {Object} Objeto com name, code e flag do país
     */
    const getCountryInfo = () => {
      const countryCode = route.params.country.toUpperCase()
      return resolveCountry(countryCode, null)
    }

    /**
     * Retorna o título da página formatado com o nome do país
     * 
     * @returns {string} Título no formato "Filmes [País]"
     */
    const getCountryTitle = () => {
      const info = getCountryInfo()
      return `Filmes ${info.name}`;
    }

    /**
     * Retorna a descrição da página com o nome do país
     * 
     * @returns {string} Descrição no formato "Explore os melhores filmes de [País]"
     */
    const getCountryDescription = () => {
      const info = getCountryInfo()
      return `Explore os melhores filmes de ${info.name}`;
    }

    /**
     * Carrega filmes do país com filtros aplicados
     * 
     * @param {number} page - Número da página para paginação
     */
    const loadMovies = async (page = 1) => {
      try {
        if (page === 1) {
          loading.value = true;
        } else {
          isLoadingMore.value = true;
        }
        
        const countryInfo = getCountryInfo()
        
        const params = {
          page,
          limit: 20
        }

        // Adicionar filtros se existirem
        if (filters.value.genre) {
          params.genre = filters.value.genre
        }
        if (filters.value.yearFrom) {
          params.yearFrom = filters.value.yearFrom
        }
        if (filters.value.yearTo) {
          params.yearTo = filters.value.yearTo
        }
        if (filters.value.minRating) {
          params.minRating = filters.value.minRating
        }
        
        const response = await axios.get(`/api/movies/country/${countryInfo.code}`, { params })
        
        movies.value = response.data.data;
        currentPage.value = response.data.meta?.current_page || response.data.current_page || 1;
        totalPages.value = response.data.meta?.last_page || response.data.last_page || 1;
      } catch (error) {
        console.error('Erro ao carregar filmes:', error)
      } finally {
        loading.value = false;
        isLoadingMore.value = false;
      }
    }

    /**
     * Navega para uma página específica da paginação
     * 
     * @param {number} page - Número da página destino
     */
    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        router.push({ query: { ...route.query, page } });
        window.scrollTo({ top: 0, behavior: 'smooth' });
        loadMovies(page);
      }
    }

    /**
     * Calcula os números de página a serem exibidos na paginação
     * com reticências quando necessário
     * 
     * @returns {Array} Array com números de página e strings '...' para reticências
     */
    const getPageNumbers = () => {
      const pages = [];
      const current = currentPage.value;
      const total = totalPages.value;
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(total);
        } else if (current >= total - 3) {
          pages.push(1);
          pages.push('...');
          for (let i = total - 4; i <= total; i++) {
            pages.push(i);
          }
        } else {
          pages.push(1);
          pages.push('...');
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(total);
        }
      }
      
      return pages
    }

    /**
     * Aplica os filtros selecionados e recarrega a lista de filmes
     */
    const applyFilters = () => {
      currentPage.value = 1;
      router.push({ query: { page: 1 } });
      loadMovies(1);
    }

    /**
     * Limpa todos os filtros e recarrega a lista completa de filmes
     */
    const clearFilters = () => {
      filters.value = {
        genre: '',
        yearFrom: '',
        yearTo: '',
        minRating: ''
      }
      currentPage.value = 1;
      router.push({ query: {} });
      loadMovies(1);
    }

    /**
     * Atualiza as meta tags da página para SEO com informações do país
     */
    const updateMetaTags = () => {
      const info = getCountryInfo();
      useHead({
        title: `Filmes ${info.name} - Guia de Filmes`,
        meta: [
          { name: 'description', content: `Descubra os melhores filmes de ${info.name}. Veja títulos populares, clássicos e lançados em alta do cinema ${info.name.toLowerCase()}.` },
          { name: 'keywords', content: `filmes ${info.name.toLowerCase()}, cinema ${info.name.toLowerCase()}, filmes, ${info.name}` },
          { property: 'og:title', content: `Filmes ${info.name} - Guia de Filmes` },
          { property: 'og:description', content: `Descubra os melhores filmes de ${info.name}` }
        ]
      })
    }

    onMounted(() => {
      updateMetaTags();
      const pageFromUrl = parseInt(route.query.page) || 1;
      currentPage.value = pageFromUrl;
      loadMovies(pageFromUrl);
    })

    watch(() => route.params.country, () => {
      updateMetaTags();
      currentPage.value = 1;
      movies.value = [];
      router.push({ query: {} });
      clearFilters();
    })

    return {
      loading,
      isLoadingMore,
      movies,
      currentPage,
      totalPages,
      currentYear,
      filters,
      getCountryInfo,
      getCountryTitle,
      getCountryDescription,
      applyFilters,
      clearFilters,
      goToPage,
      getPageNumbers
    }
  }
}
</script>

<style scoped>
/* Container principal da página de filmes por país */
.country-movies {
  min-height: 100vh;
}

/* Hero section com background da bandeira do país */
.hero {
  position: relative;
  overflow: hidden;
  background-repeat: no-repeat;
  background-size: cover;
}

/* Background da bandeira do país */
.hero-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-size: cover;
  background-position: center;
  filter: blur(8px) brightness(0.4);
  transform: scale(1.1);
  z-index: 0;
}

/* Overlay escuro sobre a bandeira para melhor legibilidade do texto */
.hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.5));
  z-index: 1;
}

/* Posiciona o conteúdo acima do overlay */
.hero-body {
  position: relative;
  z-index: 2;
}

/* Estado vazio quando nenhum filme é encontrado */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--text-muted);
}

.empty-state .icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  color: var(--primary-color);
}

.empty-state p {
  font-size: 1.5rem;
}

/* Spinner de loading - animação circular */
.spinner {
  border: 4px solid rgba(229, 9, 20, 0.1);
  border-left-color: #e50914;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 2rem auto;
}

/* Animação de rotação do spinner */
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Estilo para página atual na paginação */
.pagination-link.is-current {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}

/* Estilos para selects (filtros) */
.select select {
  background-color: var(--background-dark);
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}

.select select:hover {
  border-color: var(--primary-color);
}

/* Bandeira do país no título da página */
.country-flag-title {
  width: 48px;
  height: 36px;
  border-radius: 4px;
  object-fit: cover;
  margin-right: 12px;
  vertical-align: middle;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

/* Inputs de texto (filtros de ano e nota) */
.input {
  background-color: var(--background-dark);
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}

.input:hover, .input:focus {
  border-color: var(--primary-color);
}

.input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

/* Estilos de paginação */
.pagination {
  margin-top: 3rem;
  margin-bottom: 2rem;
}

/* Botões de paginação - estado normal */
.pagination-previous,
.pagination-next,
.pagination-link {
  background-color: #2b2b2b;
  border-color: #404040;
  color: #e0e0e0;
  transition: all 0.3s ease;
}

/* Botões de paginação - estado hover */
.pagination-previous:hover:not(:disabled),
.pagination-next:hover:not(:disabled),
.pagination-link:hover:not(.is-current) {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  transform: translateY(-2px);
}

/* Página atual - destaque vermelho */
.pagination-link.is-current {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  font-weight: 600;
}

/* Botões de paginação - estado desabilitado */
.pagination-previous:disabled,
.pagination-next:disabled {
  background-color: #1a1a1a;
  border-color: #2b2b2b;
  color: #555;
  cursor: not-allowed;
  opacity: 0.5;
}

/* Reticências entre números de página */
.pagination-ellipsis {
  color: #888;
}
</style>
