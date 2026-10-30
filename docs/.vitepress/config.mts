import { defineConfig } from 'vitepress'

export default defineConfig({
  base: process.env.VITEPRESS_BASE || '/torob-laravel/',
  title: 'Torob Laravel',
  description: 'Laravel integration package for Torob Products and Orders Sync',
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'Inbound APIs', link: '/inbound/product-api-v3' },
      { text: 'Outbound APIs', link: '/outbound/webhook' },
      { text: 'Cookbook', link: '/examples/e-commerce-cookbook' },
    ],
    sidebar: [
      {
        text: 'Overview & Setup',
        items: [
          { text: 'What is Torob Sync?', link: '/guide/what-is-torob' },
          { text: 'Getting Started', link: '/guide/getting-started' },
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'Security & Architecture', link: '/guide/architecture' },
        ],
      },
      {
        text: 'Inbound APIs (Torob → Shop)',
        items: [
          { text: 'Product API v3', link: '/inbound/product-api-v3' },
          { text: 'Order Tracking API', link: '/inbound/order-tracking' },
          { text: 'Action Tracking API', link: '/inbound/action-tracking' },
          { text: 'Click Attribution (torob_clid)', link: '/inbound/click-attribution' },
        ],
      },
      {
        text: 'Outbound APIs (Shop → Torob)',
        items: [
          { text: 'Product Webhook Client', link: '/outbound/webhook' },
          { text: 'Shop API Client', link: '/outbound/shop-api' },
          { text: 'Shop Generator Connection', link: '/outbound/shop-generator' },
        ],
      },
      {
        text: 'Cookbook & Recipes',
        items: [
          { text: 'Complete E-Commerce Store', link: '/examples/e-commerce-cookbook' },
          { text: 'Queued Real-Time Webhooks', link: '/examples/queued-webhooks' },
          { text: 'Multi-Tenant Shop Generator', link: '/examples/shop-generator-cookbook' },
        ],
      },
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/eamirgh/torob-laravel' },
    ],
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2026 Amir Ghaffari',
    },
    search: {
      provider: 'local',
    },
  },
})
