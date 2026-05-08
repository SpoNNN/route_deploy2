import { defineStore } from 'pinia'

export const useRouteStore = defineStore('route', {
    state: () => ({
        currentRoute: 'routeAll',
        currentPage: 'way',
    }),
    actions: {
        setRoute(route) {
            this.currentRoute = route
        },
        setPage(page) {
            this.currentPage = page
        },
    },
})