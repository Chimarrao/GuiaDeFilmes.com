<template>
  <div class="in-theaters-page">
    <!-- Hero Section -->
    <section class="hero is-medium is-primary">
      <div class="hero-body">
        <div class="container has-text-centered">
          <p class="title is-1 has-text-white">
            <span class="icon-text">
              <span class="icon">
                <i class="fas fa-ticket-alt"></i>
              </span>
              <span>Em Cartaz</span>
            </span>
          </p>
          <p class="subtitle is-4 has-text-white-ter">
            Filmes disponíveis nos cinemas agora
          </p>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>

    <!-- Movies Grid -->
    <section v-else class="section">
      <div class="container">
        <div v-if="movies && movies.length" class="columns is-multiline">
          <div v-for="movie in movies" :key="movie.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
            <MovieCard :movie="movie" />
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>Nenhum filme em cartaz no momento</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useMovieStore } from '../store/movie.js'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'

export default {
  name: 'InTheaters',
  components: { MovieCard },
  setup() {
    const store = useMovieStore()
    const loading = ref(true)

    // SEO
    useHead({
      title: 'Filmes em Cartaz - CineRadar',
      meta: [
        { name: 'description', content: 'Descubra quais filmes estão em cartaz nos cinemas. Confira a lista completa de filmes disponíveis para assistir agora mesmo.' },
        { property: 'og:title', content: 'Filmes em Cartaz - CineRadar' },
        { property: 'og:description', content: 'Filmes disponíveis nos cinemas agora' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: window.location.href },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'Filmes em Cartaz - CineRadar' },
        { name: 'twitter:description', content: 'Veja os filmes que estão passando nos cinemas' }
      ],
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'ItemList',
            name: 'Filmes em Cartaz nos Cinemas',
            description: 'Lista de filmes atualmente disponíveis nos cinemas',
            url: window.location.href
          })
        }
      ]
    })

    const movies = computed(() => store.movies)

    const loadMovies = async () => {
      try {
        loading.value = true
        await store.fetchMovies('in-theaters')
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
      movies
    }
  }
}
</script>

<style scoped>
.in-theaters-page {
  min-height: 100vh;
}
</style>