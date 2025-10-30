<template>
  <div class="movie-card">
    <router-link :to="'/filme/' + movie.slug">
      <div class="image-container">
        <div class="card-image-wrapper">
          <img 
            :src="getPosterUrl()" 
            :alt="movie.title"
            loading="lazy"
            class="card-image"
          />
        </div>
        <div class="rating-badge-container" v-if="movie.tmdb_rating">
          <RatingBadge :score="movie.tmdb_rating" />
        </div>
      </div>
      <div class="card-content">
        <h3 class="title is-6 has-text-white">{{ movie.title }}</h3>
        
        <p class="subtitle is-7 has-text-grey-light">
          <span class="icon-text">
            <span class="icon">
              <i class="fas fa-calendar-alt"></i>
            </span>
            <span>{{ formatDate(movie.release_date) }}</span>
          </span>
        </p>

        <div class="genres" v-if="movie.genres && movie.genres.length">
          <span v-for="genre in movie.genres.slice(0, 3)" :key="genre" class="tag is-small">
            {{ genre }}
          </span>
        </div>

        <div class="card-footer">
          <span class="button is-primary is-small is-fullwidth">
            <span class="icon">
              <i class="fas fa-info-circle"></i>
            </span>
            <span>Ver Detalhes</span>
          </span>
        </div>
      </div>
    </router-link>
  </div>
</template>

<script>
import RatingBadge from './RatingBadge.vue'

export default {
  name: 'MovieCard',
  components: {
    RatingBadge
  },
  props: {
    movie: {
      type: Object,
      required: true
    }
  },
  methods: {
    getPosterUrl() {
      if (this.movie.poster_url) {
        return this.movie.poster_url
      }
      // Placeholder melhor - usando um ícone SVG de filme
      return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="500" height="750" viewBox="0 0 500 750"%3E%3Crect width="500" height="750" fill="%231a1a1a"/%3E%3Cpath d="M250 275L200 325H225V425H275V325H300L250 275Z M350 450H150C136.2 450 125 461.2 125 475V500C125 513.8 136.2 525 150 525H350C363.8 525 375 513.8 375 500V475C375 461.2 363.8 450 350 450Z" fill="%23e50914"/%3E%3Ctext x="250" y="600" font-family="Arial, sans-serif" font-size="20" fill="%23666" text-anchor="middle"%3ESEM POSTER%3C/text%3E%3C/svg%3E'
    },
    formatDate(date) {
      if (!date) return 'Data não disponível'
      const options = { year: 'numeric', month: 'long', day: 'numeric' }
      return new Date(date).toLocaleDateString('pt-BR', options)
    }
  }
}
</script>

<style scoped>
.movie-card {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.movie-card a {
  text-decoration: none;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.image-container {
  position: relative;
  margin-bottom: 1rem;
}

.card-image-wrapper {
  position: relative;
  width: 100%;
  overflow: hidden;
  border-radius: 8px;
  background-color: #1a1a1a;
}

.card-image {
  width: 100%;
  height: auto;
  display: block;
  transition: transform 0.3s;
}

.movie-card:hover .card-image {
  transform: scale(1.05);
}

.rating-badge-container {
  position: absolute;
  bottom: -15px;
  left: 10px;
  z-index: 10;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.8));
}

.card-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding-top: 0.5rem;
}

.card-footer {
  margin-top: auto;
  padding-top: 1rem;
}
</style>