<template>
  <nav class="bottom-nav">
    <RouterLink class="nav-item" to="/main">
      <img src="../assets/icons/main.svg" alt="메인" />
      <img src="../assets/icons/main-active.svg" alt="메인" />
    </RouterLink>

    <RouterLink class="nav-item" to="/search">
      <img src="../assets/icons/search.svg" alt="검색" />
      <img src="../assets/icons/search-active.svg" alt="검색" />
    </RouterLink>

    <RouterLink class="nav-item" to="/calendar">
      <img src="../assets/icons/calendar.svg" alt="캘린더" />
      <img src="../assets/icons/calendar-active.svg" alt="캘린더" />
    </RouterLink>

    <RouterLink class="nav-item" to="/my-page">
      <img src="../assets/icons/menu.svg" alt="전체메뉴" />
      <img src="../assets/icons/menu.svg" alt="전체메뉴" />
    </RouterLink>
  </nav>
</template>
