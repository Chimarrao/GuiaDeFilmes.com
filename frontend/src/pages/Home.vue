<template>
  <div class="home">
    <!-- Hero Section -->
    <section class="hero is-dark is-medium">
      <div class="hero-body">
        <div class="container has-text-centered">
          <h1 class="title">
            <span class="icon-text">
              <span class="icon has-text-danger is-large">
                <i class="fas fa-film fa-3x"></i>
              </span>
            </span>
            <br>
            Bem-vindo ao CineRadar
          </h1>
          <p class="subtitle">
            Seu radar cinematográfico: Descubra, explore e acompanhe os melhores filmes com conteúdo gerado por IA
          </p>
        </div>
      </div>
    </section>

    <div class="container">
      <!-- Em Cartaz Section -->
      <section class="section">
        <div class="section-header">
          <span class="icon">
            <i class="fas fa-ticket-alt"></i>
          </span>
          <h2>Em Cartaz</h2>
        </div>
        
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
        </div>

        <div v-else-if="inTheatersMovies.length" class="columns is-multiline">
          <div v-for="movie in inTheatersMovies" :key="movie.id" class="column is-one-third-desktop is-half-tablet">
            <MovieCard :movie="movie" />
          </div>
        </div>

        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>Nenhum filme em cartaz no momento</p>
        </div>

        <div class="has-text-centered mt-5" v-if="inTheatersMovies.length">
          <router-link to="/em-cartaz" class="button is-primary is-medium">
            <span class="icon">
              <i class="fas fa-arrow-right"></i>
            </span>
            <span>Ver Todos em Cartaz</span>
          </router-link>
        </div>
      </section>

      <!-- Próximas Estreias Section -->
      <section class="section">
        <div class="section-header">
          <span class="icon">
            <i class="fas fa-star"></i>
          </span>
          <h2>Próximas Estreias</h2>
        </div>

        <div v-if="loading" class="loading">
          <div class="spinner"></div>
        </div>

        <div v-else-if="upcomingMovies.length" class="columns is-multiline">
          <div v-for="movie in upcomingMovies" :key="movie.id" class="column is-one-third-desktop is-half-tablet">
            <MovieCard :movie="movie" />
          </div>
        </div>

        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-calendar-alt"></i>
          </span>
          <p>Nenhuma estreia programada no momento</p>
        </div>

        <div class="has-text-centered mt-5" v-if="upcomingMovies.length">
          <router-link to="/estreias" class="button is-primary is-medium">
            <span class="icon">
              <i class="fas fa-arrow-right"></i>
            </span>
            <span>Ver Todas as Estreias</span>
          </router-link>
        </div>
      </section>

      <!-- Lançamentos Recentes Section -->
      <section class="section">
        <div class="section-header">
          <span class="icon">
            <i class="fas fa-fire"></i>
          </span>
          <h2>Lançamentos Recentes</h2>
        </div>

        <div v-if="loading" class="loading">
          <div class="spinner"></div>
        </div>

        <div v-else-if="recentMovies.length" class="columns is-multiline">
          <div v-for="movie in recentMovies" :key="movie.id" class="column is-one-third-desktop is-half-tablet">
            <MovieCard :movie="movie" />
          </div>
        </div>

        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film-alt"></i>
          </span>
          <p>Nenhum lançamento recente</p>
        </div>

        <div class="has-text-centered mt-5" v-if="recentMovies.length">
          <router-link to="/lancamentos" class="button is-primary is-medium">
            <span class="icon">
              <i class="fas fa-arrow-right"></i>
            </span>
            <span>Ver Todos os Lançamentos</span>
          </router-link>
        </div>
      </section>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useMovieStore } from '../store/movie.js'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'

export default {
  name: 'Home',
  components: { MovieCard },
  setup() {
    const store = useMovieStore()
    const loading = ref(true)
    const inTheatersMovies = ref([])
    const upcomingMovies = ref([])
    const recentMovies = ref([])

    // SEO Meta Tags
    useHead({
      title: 'CineRadar - Descubra os Melhores Filmes',
      meta: [
        { name: 'description', content: 'CineRadar: Descubra filmes em cartaz, próximas estreias e lançamentos recentes. Catálogo completo com sinopses geradas por IA e informações detalhadas.' },
        { property: 'og:title', content: 'CineRadar - Seu Radar Cinematográfico' },
        { property: 'og:description', content: 'Explore o melhor do cinema: filmes em cartaz, estreias e lançamentos com conteúdo gerado por IA.' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: window.location.href },
        { property: 'og:image', content: '/og-image.jpg' },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'CineRadar - Descubra os Melhores Filmes' },
        { name: 'twitter:description', content: 'Explore filmes em cartaz, estreias e lançamentos recentes' }
      ],
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            name: 'CineRadar',
            description: 'Catálogo de filmes com informações geradas por IA',
            url: window.location.origin
          })
        }
      ]
    })

    const loadMovies = async () => {
      try {
        loading.value = true
        
        // Carregar filmes em cartaz
        await store.fetchMovies('in-theaters')
        inTheatersMovies.value = store.movies.slice(0, 6)
        
        // Carregar próximas estreias
        await store.fetchMovies('upcoming')
        upcomingMovies.value = store.movies.slice(0, 6)
        
        // Carregar lançamentos recentes
        await store.fetchMovies('released')
        recentMovies.value = store.movies.slice(0, 6)
      } catch (error) {
        console.error('Erro ao carregar filmes:', error)
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      loadMovies()
    })

    return {
      loading,
      inTheatersMovies,
      upcomingMovies,
      recentMovies
    }
  }
}
</script>