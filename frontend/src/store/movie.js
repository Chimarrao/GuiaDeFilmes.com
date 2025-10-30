import { defineStore } from 'pinia'
import axios from 'axios'

const api = axios.create({
  baseURL: 'https://codebr.net/cineradar/api'
})

export const useMovieStore = defineStore('movie', {
  state: () => ({
    movies: [],
    currentMovie: null
  }),
  actions: {
    async fetchMovies(status = null) {
      const url = status ? `/movies/${status}` : '/movies'
      const response = await api.get(url)
      this.movies = response.data.data
    },
    async fetchMovie(slug) {
      const response = await api.get(`/movie/${slug}`)
      this.currentMovie = response.data
    }
  }
})