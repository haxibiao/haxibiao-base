<template>
  <div class="recommend-follower">
    <div class="plate-title">
      推荐作者
      <a   href="javascript:;" class="right" @click="fetchData"
        ><i class="iconfont icon-shuaxin" ref="fresh"></i>换一批</a
      >
    </div>
    <ul>
      <li v-for="user in users" v-bind:key="user.id" class="user-info">
        <a   :href="'/user/' + user.id" class="avatar"
          ><img :src="user.avatar" alt=""
        /></a>

        <a
          v-if="!user.is_followed"
          href="javascript:;"
          class="follow"
          @click="toggleFollow(user)"
          >＋ 关注</a
        >

        <a
          v-else
          href="javascript:;"
          class="toggle-followed"
          @click="toggleFollow(user)"
        >
          <i class="gougou iconfont icon-weibiaoti12"></i>
          <i class="chacha iconfont icon-cha"></i>
        </a>

        <a   :href="'/user/' + user.id" class="title">{{
          user.name
        }}</a>
        <p class="info">
          写了{{ user.count_words }}字 · {{ user.count_likes }}喜欢
        </p>
      </li>
    </ul>
    <a href="/user"   class="btn-base btn-gray">
      查看全部<i class="iconfont icon-youbian"></i>
    </a>
  </div>
</template>

<script>
export default {
  name: "RecommendAuthors",

  props: ["isLogin"],

  computed: {
    apiUrl() {
      var api = "/api/user/recommend";
      var page = this.page;
      var api_url =
        api.indexOf("?") !== -1 ? api + "&page=" + page : api + "?page=" + page;
      return api_url;
    },
  },

  mounted() {
    this.fetchData();
  },

  methods: {
    fetchData() {
      var vm = this;
      this.counter++;
      $(this.$refs.fresh).css("transform", `rotate(${360 * this.counter}deg)`);
      $(".recommend_author ul").fadeOut();
      var api = vm.apiUrl;
      if (vm.isLogin) {
        api = window.tokenize(vm.apiUrl);
      }
      window.axios.get(api).then(function (response) {
        vm.users = response.data.data;
        $(".recommend_author ul").fadeIn();
      });
      vm.page++;
    },
    toggleFollow(user) {
      var _this = this;
      if (!_this.isLogin) {
        location.href = "/login";
      }
      var api_url = window.tokenize("/api/follow/" + user.id + "/users");
      window.axios.post(api_url).then(function (response) {
        user.is_followed = response.data;
        _this.$set(_this.users, _this.users.indexOf(user), user);
      });
    },
  },

  data() {
    return {
      users: [],
      counter: 1,
      page: 1,
    };
  },
};
</script>

<style lang="scss">
</style>