import { fileURLToPath, URL } from 'node:url';

import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const envDir = fileURLToPath(new URL('.', import.meta.url));
  const env = loadEnv(mode, envDir, '');
  const useProxy = env.DEV_USE_PROXY !== 'false';
  const proxyPrefixRaw = env.DEV_PROXY_PREFIX || '/backend';
  const proxyPrefix = proxyPrefixRaw.startsWith('/') ? proxyPrefixRaw : `/${proxyPrefixRaw}`;
  const proxyTarget = env.DEV_PROXY_TARGET || 'http://localhost/TONE';

  return {
    plugins: [vue(), vueDevTools()],
    server: useProxy
      ? {
          proxy: {
            [proxyPrefix]: {
              target: proxyTarget,
              changeOrigin: true
            }
          }
        }
      : undefined,
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    }
  };
});
