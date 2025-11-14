<template>
  <div class="search-page">
    <!-- Page Header -->
    <section class="hero is-dark">
      <div class="hero-body">
        <div class="container">
          <h1 class="title is-1 has-text-white">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-search"></i>
              </span>
              <span>Buscar Filmes</span>
            </span>
          </h1>
          <p class="subtitle has-text-white-ter">Encontre seus filmes favoritos com busca simples ou filtros avançados</p>
          
          <!-- Tabs -->
          <div class="tabs is-boxed is-large mt-5">
            <ul>
              <li :class="{ 'is-active': searchMode === 'simple' }">
                <a @click="searchMode = 'simple'">
                  <span class="icon is-small"><i class="fas fa-search"></i></span>
                  <span>Busca Simples</span>
                </a>
              </li>
              <li :class="{ 'is-active': searchMode === 'advanced' }">
                <a @click="searchMode = 'advanced'">
                  <span class="icon is-small"><i class="fas fa-sliders"></i></span>
                  <span>Filtros Avançados</span>
                </a>
              </li>
            </ul>
          </div>

          <!-- Simple Search -->
          <div v-if="searchMode === 'simple'" class="mt-4">
            <div class="field">
              <p class="control has-icons-left is-large">
                <input 
                  class="input is-large" 
                  type="search" 
                  placeholder="Digite o nome do filme..." 
                  v-model="searchQuery"
                  @keyup.enter="performSimpleSearch"
                  autofocus
                >
                <span class="icon is-left">
                  <i class="fas fa-search"></i>
                </span>
              </p>
            </div>
            
            <button class="button is-primary is-large mt-3" @click="performSimpleSearch">
              <span class="icon">
                <i class="fas fa-search"></i>
              </span>
              <span>Buscar</span>
            </button>
          </div>

          <!-- Advanced Filters -->
          <div v-else class="box mt-4">
            <div class="columns is-multiline">
              <!-- Genre Filter -->
              <div class="column is-half">
                <div class="field">
                  <label class="label has-text-white">Gênero</label>
                  <div class="control has-icons-left">
                    <div class="select is-fullwidth">
                      <select v-model="filters.genre">
                        <option value="">Todos os gêneros</option>
                        <option v-for="genre in genres" :key="genre.slug" :value="genre.slug">
                          {{ genre.name }}
                        </option>
                      </select>
                    </div>
                    <span class="icon is-left">
                      <i class="fas fa-masks-theater"></i>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Year Range -->
              <div class="column is-half">
                <div class="field">
                  <label class="label has-text-white">Ano de Lançamento</label>
                  <div class="field has-addons">
                    <div class="control is-expanded has-icons-left">
                      <input v-model="filters.yearFrom" type="number" class="input" placeholder="De" min="1900" :max="currentYear">
                      <span class="icon is-left">
                        <i class="fas fa-calendar"></i>
                      </span>
                    </div>
                    <div class="control">
                      <a class="button is-static">até</a>
                    </div>
                    <div class="control is-expanded">
                      <input v-model="filters.yearTo" type="number" class="input" placeholder="Até" min="1900" :max="currentYear">
                    </div>
                  </div>
                </div>
              </div>

              <!-- Rating Filter -->
              <div class="column is-half">
                <div class="field">
                  <label class="label has-text-white">Nota Mínima</label>
                  <div class="control has-icons-left">
                    <input v-model="filters.minRating" type="number" class="input" placeholder="Ex: 7.0" min="0" max="10" step="0.1">
                    <span class="icon is-left">
                      <i class="fas fa-star"></i>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Country Filter -->
              <div class="column is-half">
                <div class="field">
                  <label class="label has-text-white">País de Origem</label>
                  <div class="control has-icons-left">
                    <input v-model="filters.country" type="text" class="input" placeholder="Ex: Estados Unidos, Brasil">
                    <span class="icon is-left">
                      <i class="fas fa-globe"></i>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Sort By -->
              <div class="column is-half">
                <div class="field">
                  <label class="label has-text-white">Ordenar por</label>
                  <div class="control has-icons-left">
                    <div class="select is-fullwidth">
                      <select v-model="filters.sortBy">
                      <option value="popularity">Popularidade</option>
                      <option value="rating">Nota (Maior para Menor)</option>
                      <option value="vote_count">Mais Votados</option>
                      <option value="release_date">Mais Recentes</option>
                      <option value="release_date_asc">Mais Antigos</option>
                      <option value="title">Título (A-Z)</option>
                      <option value="title_desc">Título (Z-A)</option>
                    </select>
                    </div>
                    <span class="icon is-left">
                      <i class="fas fa-arrow-down-wide-short"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="field is-grouped is-grouped-centered mt-5">
              <div class="control">
                <button @click="applyFilters" class="button is-danger is-medium" :class="{ 'is-loading': loading }">
                  <span class="icon">
                    <i class="fas fa-search"></i>
                  </span>
                  <span>Buscar Filmes</span>
                </button>
              </div>
              <div class="control">
                <button @click="clearFilters" class="button is-light is-medium">
                  <span class="icon">
                    <i class="fas fa-rotate-left"></i>
                  </span>
                  <span>Limpar Filtros</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Search Results -->
    <section class="section">
      <div class="container">
        <!-- Loading State -->
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
        </div>

        <!-- Results -->
        <div v-else-if="results.length > 0">
          <h2 class="title is-3 has-text-white mb-5">
            <template v-if="searchMode === 'simple'">
              Resultados para "{{ lastSearchQuery }}" ({{ results.length }} encontrados)
            </template>
            <template v-else>
              Resultados 
              <span class="tag is-danger is-medium ml-2">{{ totalResults }}</span>
            </template>
          </h2>
          
          <div class="columns is-multiline is-mobile">
            <div v-for="movie in results" :key="movie.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
              <MovieCard :movie="movie" />
            </div>
          </div>

          <!-- Loading More (Advanced mode only) -->
          <div v-if="isLoadingMore && showLoadMore" class="has-text-centered py-5">
            <div class="spinner"></div>
          </div>

          <!-- Infinite Scroll Trigger (Advanced mode only) -->
          <div v-if="showLoadMore" ref="loadMoreTrigger" class="load-more-trigger"></div>
        </div>

        <!-- No Results -->
        <div v-else-if="hasSearched" class="empty-state">
          <span class="icon">
            <i class="fas fa-search"></i>
          </span>
          <template v-if="searchMode === 'simple'">
            <p>Nenhum filme encontrado para "{{ lastSearchQuery }}"</p>
            <p class="has-text-white-ter">Tente buscar com outros termos</p>
          </template>
          <template v-else>
            <p>Nenhum filme encontrado com esses critérios</p>
            <p class="has-text-white-ter">Tente ajustar os filtros e buscar novamente</p>
          </template>
        </div>

        <!-- Initial State -->
        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>{{ searchMode === 'simple' ? 'Digite o nome de um filme para buscar' : 'Configure os filtros e clique em Buscar Filmes' }}</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMovieStore } from '../store/movie.js'
import { useHead } from '../composables/useHead.js'
import { useInfiniteScroll } from '../composables/useInfiniteScroll.js'
import MovieCard from '../components/MovieCard.vue'
import api from '../services/api.js'

export default {
  name: 'Search',
  components: {
    MovieCard
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const store = useMovieStore()
    const loading = ref(false)
    const searchQuery = ref('')
    const lastSearchQuery = ref('')
    const results = ref([])
    const searchMode = ref('simple')
    
    // Advanced filters
    const currentPage = ref(1)
    const hasMore = ref(true)
    const totalResults = ref(0)
    const loadMoreTrigger = ref(null)
    const hasSearched = ref(false)
    const isLoadingMore = ref(false)
    
    const currentYear = new Date().getFullYear()
    
    const filters = ref({
      genre: '',
      yearFrom: '',
      yearTo: '',
      minRating: '',
      country: '',
      sortBy: 'popularity'
    })

    const genres = ref([
      { name: 'Ação', slug: 'acao' },
      { name: 'Aventura', slug: 'aventura' },
      { name: 'Comédia', slug: 'comedia' },
      { name: 'Drama', slug: 'drama' },
      { name: 'Ficção Científica', slug: 'ficcao-cientifica' },
      { name: 'Terror', slug: 'terror' },
      { name: 'Romance', slug: 'romance' },
      { name: 'Suspense', slug: 'suspense' },
      { name: 'Animação', slug: 'animacao' },
      { name: 'Crime', slug: 'crime' },
      { name: 'Documentário', slug: 'documentario' },
      { name: 'Família', slug: 'familia' },
      { name: 'Fantasia', slug: 'fantasia' },
      { name: 'Guerra', slug: 'guerra' },
      { name: 'História', slug: 'historia' },
      { name: 'Mistério', slug: 'misterio' },
      { name: 'Musical', slug: 'musical' },
      { name: 'Western', slug: 'western' },
    ])

    useHead({
      title: 'Buscar Filmes - Guia de Filmes',
      meta: [
        { name: 'description', content: 'Busque por seus filmes favoritos no Guia de Filmes' }
      ]
    })

    /**
     * Realiza busca simples por título de filme
     */
    const performSimpleSearch = async () => {
      const query = searchQuery.value.trim()
      if (!query) return

      try {
        loading.value = true
        lastSearchQuery.value = query
        hasSearched.value = true
        
        // Atualiza URL com parâmetro de busca
        router.push({ query: { q: query } })
        
        // Chama API backend com parâmetro q
        const response = await api.get('/movies/search', { 
          params: { q: query } 
        })
        
        // Laravel retorna os dados em response.data.data (paginação)
        results.value = response.data.data || []
      } catch (error) {
        console.error('Erro ao buscar filmes:', error)
        results.value = []
      } finally {
        loading.value = false
      }
    }

    /**
     * Busca filmes com filtros avançados e paginação infinita
     * @param {number} page - Número da página a ser carregada
     */
    const fetchFilteredMovies = async (page = 1) => {
      try {
        if (page === 1) {
          loading.value = true
          results.value = []
        } else {
          isLoadingMore.value = true
        }

        const params = { page, ...filters.value }
        
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })

        const response = await api.get('/movies/filter', { params })

        if (page === 1) {
          results.value = response.data.data
        } else {
          results.value.push(...response.data.data)
        }

        hasMore.value = response.data.current_page < response.data.last_page
        currentPage.value = response.data.current_page
        totalResults.value = response.data.total
        hasSearched.value = true
      } catch (error) {
        console.error('Erro ao buscar filmes com filtros:', error)
      } finally {
        loading.value = false
        isLoadingMore.value = false
      }
    }

    /**
     * Aplica os filtros avançados e reinicia a busca
     */
    const applyFilters = () => {
      currentPage.value = 1
      hasMore.value = true
      fetchFilteredMovies()
    }

    /**
     * Limpa todos os filtros e reseta resultados
     */
    const clearFilters = () => {
      filters.value = {
        genre: '',
        yearFrom: '',
        yearTo: '',
        minRating: '',
        country: '',
        sortBy: 'popularity'
      }
      results.value = []
      hasSearched.value = false
    }

    /**
     * Carrega mais resultados quando usuário rola até o final (infinite scroll)
     */
    const loadMore = () => {
      if (!isLoadingMore.value && hasMore.value && searchMode.value === 'advanced') {
        fetchFilteredMovies(currentPage.value + 1)
      }
    }

    useInfiniteScroll(loadMoreTrigger, loadMore, hasMore)

    onMounted(() => {
      const q = route.query.q
      if (q) {
        searchQuery.value = q
        performSimpleSearch()
      }
    })

    const showLoadMore = computed(() => {
      return searchMode.value === 'advanced' && hasSearched.value
    })

    return {
      loading,
      searchQuery,
      lastSearchQuery,
      results,
      searchMode,
      filters,
      genres,
      currentYear,
      totalResults,
      hasSearched,
      isLoadingMore,
      loadMoreTrigger,
      showLoadMore,
      performSimpleSearch,
      applyFilters,
      clearFilters
    }
  }
}
</script>

<style scoped>
/* Container principal da página de busca */
.search-page {
  min-height: calc(100vh - 200px);
}

/* === Estilos das Tabs === */

/* Borda inferior das tabs */
.tabs ul {
  border-bottom-color: rgba(229, 9, 20, 0.3);
}

/* Tab ativa destacada em vermelho */
.tabs li.is-active a {
  border-bottom-color: #e50914;
  color: #e50914;
}

/* Links das tabs em branco */
.tabs a {
  color: #fff;
}

/* Hover nas tabs com vermelho suave */
.tabs a:hover {
  border-bottom-color: rgba(229, 9, 20, 0.5);
  color: #e50914;
}

/* === Estilos do Formulário === */

/* Container dos filtros avançados com gradiente escuro */
.box {
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
  border: 1px solid rgba(229, 9, 20, 0.2);
}

/* === Infinite Scroll === */

/* Trigger invisível para detectar scroll */
.load-more-trigger {
  height: 1px;
}

/* === Loading Spinner === */

/* Spinner animado com cores da marca */
.spinner {
  border: 4px solid rgba(229, 9, 20, 0.1);
  border-left-color: #e50914;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

/* Animação de rotação do spinner */
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* === Estado Vazio === */

/* Container do estado sem resultados */
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
</style>
