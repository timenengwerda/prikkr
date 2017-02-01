// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import VueRouter from 'vue-router'
import VueResource from 'vue-resource'

Vue.use(VueRouter)
Vue.use(VueResource)

const routes = [
  { path: '/', component: view('event/New') }
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

/* eslint-disable no-unused-vars */
const app = new Vue({
  router
}).$mount('#app')
