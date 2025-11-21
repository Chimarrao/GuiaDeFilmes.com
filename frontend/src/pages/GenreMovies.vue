<template>
  <div class="genre-movies">
    <!-- Hero com background fixo -->
    <section class="hero is-medium is-dark">
      <!-- Background fixo com imagem do gênero -->
      <div v-if="genreBackgroundImage" class="genre-background" :style="{ backgroundImage: `url(${genreBackgroundImage})` }"></div>
      <div class="hero-body">
        <div class="container has-text-centered">
          <p class="subtitle mb-2">
            <router-link to="/explorar" class="has-text-grey-light">
              <i class="fas fa-compass"></i> Explorar
            </router-link>
            <span class="mx-2">/</span>
            Gênero
          </p>
          <h1 class="title is-1">
            <i :class="`fas ${genreIcon}`"></i> {{ genreName }}
          </h1>
          <p class="subtitle">Filmes mais populares do gênero</p>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
      <div class="container">
        <h2 class="title is-4 has-text-white mb-4">
          <span class="icon-text">
            <span class="icon has-text-danger">
              <i class="fas fa-filter"></i>
            </span>
            <span>Filtros Avançados</span>
          </span>
        </h2>
        
        <div class="columns is-multiline">
          <div class="column is-4">
            <div class="field">
              <label class="label has-text-white">Ano Inicial</label>
              <div class="control">
                <input class="input" type="number" v-model="filters.yearFrom" placeholder="Ex: 2000" min="1900" :max="currentYear">
              </div>
            </div>
          </div>

          <div class="column is-4">
            <div class="field">
              <label class="label has-text-white">Ano Final</label>
              <div class="control">
                <input class="input" type="number" v-model="filters.yearTo" placeholder="Ex: 2024" min="1900" :max="currentYear">
              </div>
            </div>
          </div>

          <div class="column is-4">
            <div class="field">
              <label class="label has-text-white">Nota Mínima</label>
              <div class="control">
                <div class="select is-fullwidth">
                  <select v-model="filters.minRating">
                    <option value="">Todas</option>
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
    </section>

    <!-- Loading State -->
    <div v-if="isLoading" class="section">
      <div class="container has-text-centered">
        <div class="spinner"></div>
        <p class="mt-4">Carregando filmes...</p>
      </div>
    </div>

    <!-- Movies Grid -->
    <section v-else class="section">
      <div class="container">
        <div v-if="movies && movies.length">
          <div class="columns is-multiline is-mobile">
            <div v-for="movie in movies" :key="movie.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
              <MovieCard :movie="movie" />
            </div>
          </div>

          <!-- Loading More -->
          <div v-if="isLoadingMore" class="has-text-centered py-5">
            <div class="spinner"></div>
          </div>

          <!-- Pagination -->
          <nav v-if="totalPages > 1" class="pagination is-centered mt-6" role="navigation" aria-label="pagination">
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

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>Nenhum filme encontrado para este gênero</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'
import api from '../services/api.js'

export default {
  name: 'GenreMovies',
  components: { MovieCard },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const movies = ref([])
    const isLoading = ref(false)
    const isLoadingMore = ref(false)
    const currentPage = ref(1)
    const totalPages = ref(1)
    const currentYear = new Date().getFullYear()
    const filters = ref({
      yearFrom: '',
      yearTo: '',
      minRating: ''
    })

    const genreSlug = computed(() => route.params.genre)
    
    const genresMap = {
      'acao': { name: 'Ação', icon: 'fa-explosion' },
      'aventura': { name: 'Aventura', icon: 'fa-compass' },
      'comedia': { name: 'Comédia', icon: 'fa-face-laugh' },
      'drama': { name: 'Drama', icon: 'fa-masks-theater' },
      'ficcao-cientifica': { name: 'Ficção Científica', icon: 'fa-rocket' },
      'terror': { name: 'Terror', icon: 'fa-ghost' },
      'romance': { name: 'Romance', icon: 'fa-heart' },
      'suspense': { name: 'Suspense', icon: 'fa-magnifying-glass' },
      'animacao': { name: 'Animação', icon: 'fa-wand-magic-sparkles' },
      'crime': { name: 'Crime', icon: 'fa-gun' },
      'documentario': { name: 'Documentário', icon: 'fa-video' },
      'familia': { name: 'Família', icon: 'fa-children' },
      'fantasia': { name: 'Fantasia', icon: 'fa-dragon' },
      'guerra': { name: 'Guerra', icon: 'fa-shield' },
      'historia': { name: 'História', icon: 'fa-landmark' },
      'misterio': { name: 'Mistério', icon: 'fa-mask' },
      'musical': { name: 'Musical', icon: 'fa-music' },
      'western': { name: 'Western', icon: 'fa-hat-cowboy' },
    }

    const genreName = computed(() => genresMap[genreSlug.value]?.name || genreSlug.value)
    const genreIcon = computed(() => genresMap[genreSlug.value]?.icon || 'fa-film')

    // Atualizar title quando o gênero mudar
    watch(genreName, (newGenreName) => {
      if (newGenreName) {
        useHead({
          title: `${newGenreName} - Explorar - Guia de Filmes`,
          meta: [
            { name: 'description', content: `Descubra os filmes mais populares do gênero ${newGenreName}` }
          ]
        })
      }
    }, { immediate: true })

    /**
     * Mapeamento de imagens de fundo disponíveis para cada gênero
     * Apenas gêneros com imagens convertidas para WebP são listados
     */
    const genreBackgrounds = {
      'acao': '/src/assets/images/acao.webp',
      'animacao': '/src/assets/images/animacao.webp',
      'aventura': '/src/assets/images/aventura.webp',
      'comedia': '/src/assets/images/comedia.webp',
      'documentario': '/src/assets/images/documentario.webp',
      'drama': '/src/assets/images/drama.webp',
      'fantasia': '/src/assets/images/fantasia.webp',
      'ficcao-cientifica': '/src/assets/images/ficcao-cientifica.webp',
      'guerra': '/src/assets/images/guerra.webp',
      'romance': '/src/assets/images/romance.webp',
      'suspense': '/src/assets/images/suspense.webp',
      'terror': '/src/assets/images/terror.webp',
    }

    /**
     * Retorna a URL da imagem de fundo para o gênero atual
     * @returns {string|null} URL da imagem ou null se não houver
     */
    const genreBackgroundImage = computed(() => genreBackgrounds[genreSlug.value] || null)

    /**
     * Busca filmes do gênero atual com filtros aplicados
     * @param {number} page - Número da página a ser carregada
     */
    const fetchMovies = async (page = 1) => {
      try {
        if (page === 1) {
          isLoading.value = true
        } else {
          isLoadingMore.value = true
        }

        const params = { 
          page: page,
          limit: 20,
          genre: genreSlug.value
        }

        // Adicionar filtros opcionais aos parâmetros
        if (filters.value.yearFrom) params.yearFrom = filters.value.yearFrom
        if (filters.value.yearTo) params.yearTo = filters.value.yearTo
        if (filters.value.minRating) params.minRating = filters.value.minRating

        const response = await api.get('/movies/filter', { params })

        movies.value = response.data.data
        currentPage.value = response.data.meta?.current_page || response.data.current_page || 1
        totalPages.value = response.data.meta?.last_page || response.data.last_page || 1
      } catch (error) {
        console.error('Erro ao buscar filmes por gênero:', error)
      } finally {
        isLoading.value = false
        isLoadingMore.value = false
      }
    }

    /**
     * Aplica os filtros selecionados e recarrega a primeira página
     */
    const applyFilters = () => {
      currentPage.value = 1
      fetchMovies(1)
    }

    /**
     * Limpa todos os filtros e recarrega a primeira página
     */
    const clearFilters = () => {
      filters.value = {
        yearFrom: '',
        yearTo: '',
        minRating: ''
      }
      currentPage.value = 1
      fetchMovies(1)
    }

    /**
     * Navega para uma página específica
     * @param {number} page - Número da página de destino
     */
    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        router.push({ query: { ...route.query, page } })
        window.scrollTo({ top: 0, behavior: 'smooth' })
        fetchMovies(page)
      }
    }

    /**
     * Gera array de números de página para paginação com elipses
     * @returns {Array} Array com números de páginas e elipses ('...')
     */
    const getPageNumbers = () => {
      const pages = []
      const current = currentPage.value
      const total = totalPages.value
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        }
      }
      
      return pages
    }

    onMounted(() => {
      const pageFromUrl = parseInt(route.query.page) || 1
      currentPage.value = pageFromUrl
      fetchMovies(pageFromUrl)
    })

    watch(genreSlug, () => {
      currentPage.value = 1
      movies.value = []
      router.push({ query: {} })
      fetchMovies(1)
    })

    return {
      movies,
      isLoading,
      isLoadingMore,
      currentPage,
      totalPages,
      genreName,
      genreIcon,
      genreBackgroundImage,
      goToPage,
      getPageNumbers,
      filters,
      currentYear,
      applyFilters,
      clearFilters
    }
  }
}
</script>

<style scoped>
/* Container principal da página de filmes por gênero */
.genre-movies {
  min-height: 100vh;
  position: relative;
  z-index: 1;
}

/* Hero com overflow para conter o background */
.hero {
  position: relative;
  overflow: hidden;
  background: transparent;
  z-index: 2;
}

/* Background com imagem do gênero (blur + escurecimento) */
.genre-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  filter: blur(8px) brightness(0.4);
  z-index: 0;
}

/* Hero body acima do background */
.hero-body {
  position: relative;
  z-index: 1;
}

/* Seções acima do background */
.filters-section,
.section {
  position: relative;
  z-index: 2;
}

/* Seção de filtros com fundo semi-transparente */
.filters-section {
  background-color: rgba(0, 0, 0, 0.3);
  padding: 2rem 0;
  margin-bottom: 2rem;
}

/* Select customizado com tema escuro */
.select select {
  background-color: #2b2b2b;
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}

/* Hover do select com cor primária */
.select select:hover {
  border-color: #dc143c;
}

/* Input customizado com tema escuro */
.input {
  background-color: #2b2b2b;
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}

/* Estados hover e focus do input */
.input:hover, .input:focus {
  border-color: #dc143c;
}

/* Placeholder com opacidade reduzida */
.input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

/* Spinner de loading animado */
.spinner {
  border: 4px solid rgba(229, 9, 20, 0.1);
  border-left-color: #e50914;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

/* Animação de rotação infinita do spinner */
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Estado vazio quando não há filmes */
.empty-state {
  text-align: center;
  padding: 5rem 2rem;
}

/* Ícone grande do estado vazio */
.empty-state .icon {
  font-size: 4rem;
  color: #666;
  margin-bottom: 1rem;
}

/* Estilos de paginação */
.pagination {
  margin-top: 3rem;
  margin-bottom: 2rem;
}

/* Botões de paginação com tema escuro */
.pagination-previous,
.pagination-next,
.pagination-link {
  background-color: #2b2b2b;
  border-color: #404040;
  color: #e0e0e0;
  transition: all 0.3s ease;
}

/* Hover nos botões de paginação com elevação */
.pagination-previous:hover:not(:disabled),
.pagination-next:hover:not(:disabled),
.pagination-link:hover:not(.is-current) {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  transform: translateY(-2px);
}

/* Página atual destacada */
.pagination-link.is-current {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  font-weight: 600;
}

/* Botões desabilitados */
.pagination-previous:disabled,
.pagination-next:disabled {
  background-color: #1a1a1a;
  border-color: #2b2b2b;
  color: #555;
  cursor: not-allowed;
  opacity: 0.5;
}

/* Elipses da paginação */
.pagination-ellipsis {
  color: #888;
}
</style>
