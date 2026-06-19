<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
          <NuxtLink to="/" class="text-xl font-bold text-gray-900">在线课程教务系统</NuxtLink>
          <nav class="flex gap-6">
            <NuxtLink to="/" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">首页</NuxtLink>
            <NuxtLink to="/courses" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">课程管理</NuxtLink>
            <NuxtLink to="/students" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">学生管理</NuxtLink>
            <NuxtLink to="/courses" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">点名批改</NuxtLink>
          </nav>
        </div>
      </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 py-6">
      <slot />
    </main>
  </div>
</template>
