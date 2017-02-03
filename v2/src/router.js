import Vue from 'vue'
import VueRouter from 'vue-router'
Vue.use(VueRouter)
const routes = [
  { path: '/', component: view('event/New') },
  { name: 'showEvent', path: '/event/:eventId/:userId', component: view('event/Show') }
]
/* eslint-disable no-new */
/* eslint-disable no-undef */
const router = new VueRouter({
  routes
})

/**
 * Asynchronously load view (Webpack Lazy loading compatible)
 * @param  {string}   name     the filename (basename) of the view to load.
 */
function view (name) {
  return function (resolve) {
    require(['./components/' + name + '.vue'], resolve)
  }
}

export default router
