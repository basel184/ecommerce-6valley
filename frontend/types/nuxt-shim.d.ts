// Minimal shims so the editor doesn't complain before Nuxt generates types
declare const defineNuxtPlugin: any
declare function useRuntimeConfig(): any
declare function useState<T>(key: string, init?: () => T): { value: T }
declare function computed<T>(fn: () => T): { value: T }
declare function watch<T>(source: any, cb: (val: T) => void): void
declare function navigateTo(path: string): Promise<void>
declare function onMounted(fn: () => void): void
declare function useRoute(): any
declare function useAsyncData<T>(key: string, handler: () => Promise<T>): Promise<{ data: { value: T }, pending: any, error: any }>

// App composables (shims)
declare function useAuth(): any
declare function useGuest(): any
declare function useApi(): any
declare function useCart(): any
declare function useProducts(): any
declare function useCookie<T>(name: string, opts?: any): { value: T }
