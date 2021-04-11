import Vue from 'vue';
import VueRouter from 'vue-router';

let routes = [
    { path: '/', redirect: '/notebooks' },
    { path: '/notebooks', component: require('../components/write/Notebooks.vue').default },
    {
        path: '/notebooks/:collectionId',
        component: require('../components/write/Notebooks.vue').default,
        props: true,
        children: [
            {
                path: 'notes/:articleId',
                component: require('../components/write/Notes.vue').default,
            },
        ],
    },
    { path: '/recycle', component: require('../components/write/Recycle.vue').default },
    { path: '/recycle/:recycleId', component: require('../components/write/Recycle.vue').default, props: true },
];

export default new VueRouter({
    // mode: 'history',
    // FIXME: 先简单处理一下 mode 导致发布页面白屏
    routes,
});
