import { defineStore } from 'pinia'
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api'
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
      console.log('Store fetchMovie chamado com slug:', slug)
      try {
        const response = await api.get(`/movie/${slug}`)
        console.log('API response:', response.data)
        this.currentMovie = response.data
        console.log('currentMovie definido:', this.currentMovie)
      } catch (error) {
        console.error('Erro no store fetchMovie:', error)
        this.currentMovie = null
      }
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