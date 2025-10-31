import { defineStore } from 'pinia'
import axios from 'axios'

const api = axios.create({
  baseURL: 'https://codebr.net/cineradar/api'
})

export const useMovieStore = defineStore('movie', {
  state: () => ({
    movies: [],
    currentMovie: null,
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 20
    }
  }),
  actions: {
    async fetchMovies(status = null, page = 1, append = false) {
      const url = status ? `/movies/${status}` : '/movies'
      const response = await api.get(url, {
        params: { page, limit: this.pagination.perPage }
      })
      
      if (append) {
        this.movies = [...this.movies, ...response.data.data]
      } else {
        this.movies = response.data.data
      }
      
      this.pagination = {
        currentPage: response.data.current_page,
        lastPage: response.data.last_page,
        total: response.data.total,
        perPage: response.data.per_page
      }
      
      return response.data
    },
    async fetchMovie(slug) {
      const response = await api.get(`/movie/${slug}`)
      this.currentMovie = response.data
    },
    resetMovies() {
      this.movies = []
      this.pagination = {
        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 20
      }
    }
  }
})