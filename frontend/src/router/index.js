import { createRouter, createWebHistory } from 'vue-router'
import Home from '../pages/Home.vue'
import MovieDetail from '../pages/MovieDetail.vue'
import Upcoming from '../pages/Upcoming.vue'
import InTheaters from '../pages/InTheaters.vue'
import Released from '../pages/Released.vue'
import About from '../pages/About.vue'
import Search from '../pages/Search.vue'

const routes = [
  { path: '/', component: Home },
  { path: '/filme/:slug', component: MovieDetail },
  { path: '/estreias', component: Upcoming },
  { path: '/em-cartaz', component: InTheaters },
  { path: '/lancamentos', component: Released },
  { path: '/sobre', component: About },
  { path: '/buscar', component: Search }
]

const router = createRouter({
  history: createWebHistory('/cineradar/'),
  routes
})

export default router