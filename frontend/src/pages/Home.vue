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
            Bem-vindo ao Guia de Filmes
          </h1>
          <p class="subtitle">
            Seu radar cinematográfico: Descubra, explore e acompanhe os melhores filmes
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

        <div v-else-if="inTheatersMovies.length" class="columns is-multiline is-mobile">
          <div v-for="movie in inTheatersMovies" :key="movie.id" class="column is-one-third-desktop is-half-tablet is-half-mobile">
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
          <Button 
            label="Ver Todos em Cartaz" 
            icon="pi pi-arrow-right" 
            class="p-button-primary p-button-lg"
            @click="$router.push('/em-cartaz')"
          />
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

        <div v-else-if="upcomingMovies.length" class="columns is-multiline is-mobile">
          <div v-for="movie in upcomingMovies" :key="movie.id" class="column is-one-third-desktop is-half-tablet is-half-mobile">
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
          <Button 
            label="Ver Todas as Estreias" 
            icon="pi pi-arrow-right" 
            class="p-button-primary p-button-lg"
            @click="$router.push('/estreias')"
          />
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

        <div v-else-if="recentMovies.length" class="columns is-multiline is-mobile">
          <div v-for="movie in recentMovies" :key="movie.id" class="column is-one-third-desktop is-half-tablet is-half-mobile">
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
          <Button 
            label="Ver Todos os Lançamentos" 
            icon="pi pi-arrow-right" 
            class="p-button-primary p-button-lg"
            @click="$router.push('/lancamentos')"
          />
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
      title: 'Guia de Filmes - Descubra os Melhores Filmes e Onde Assistir',
      meta: [
        { name: 'description', content: 'Guia de Filmes: Descubra filmes em cartaz, próximas estreias, lançamentos e onde assistir online. Catálogo completo com informações detalhadas sobre os melhores filmes.' },
        { property: 'og:title', content: 'Guia de Filmes - Seu Guia Completo de Cinema' },
        { property: 'og:description', content: 'Explore o melhor do cinema: filmes em cartaz, estreias, lançamentos e onde assistir.' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: window.location.href },
        { property: 'og:image', content: 'https://guiadefilmes.com/og-image.jpg' },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'Guia de Filmes - Descubra os Melhores Filmes' },
        { name: 'twitter:description', content: 'Explore filmes em cartaz, estreias e lançamentos recentes' },
        { name: 'twitter:image', content: 'https://guiadefilmes.com/og-image.jpg' }
      ],
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            name: 'Guia de Filmes',
            description: 'Catálogo completo de filmes com informações detalhadas e onde assistir',
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