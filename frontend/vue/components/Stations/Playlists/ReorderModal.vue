<template>
    <b-modal
        id="reorder_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Reorder Playlist')"
        :busy="loading"
        hide-footer
    >
        <b-overlay
            variant="card"
            :show="loading"
        >
            <div
                style="min-height: 40px;"
                class="flex-fill text-left bg-primary rounded mb-2"
            >
                <inline-player ref="player" />
            </div>
            <b-table-simple
                striped
                class="sortable mb-0"
            >
                <b-thead>
                    <tr>
                        <th style="width: 5%">
&nbsp;
                        </th>
                        <th style="width: 25%;">
                            {{ $gettext('Title') }}
                        </th>
                        <th style="width: 25%;">
                            {{ $gettext('Artist') }}
                        </th>
                        <th style="width: 25%;">
                            {{ $gettext('Album') }}
                        </th>
                        <th style="width: 20%;">
                            {{ $gettext('Actions') }}
                        </th>
                    </tr>
                </b-thead>
                <draggable
                    v-model="media"
                    tag="tbody"
                    @change="save"
                >
                    <tr
                        v-for="(row,index) in media"
                        :key="row.media.id"
                        class="align-middle"
                    >
                        <td class="pr-2">
                            <play-button
                                :url="row.media.links.play"
                                icon-class="lg outlined"
                            />
                        </td>
                        <td class="pl-2">
                            <span class="typography-subheading">{{ row.media.title }}</span>
                        </td>
                        <td>{{ row.media.artist }}</td>
                        <td>{{ row.media.album }}</td>
                        <td>
                            <b-button-group size="sm">
                                <b-button
                                    v-if="index+1 < media.length"
                                    size="sm"
                                    variant="primary"
                                    :title="$gettext('Down')"
                                    @click.prevent="moveDown(index)"
                                >
                                    <icon icon="arrow_downward" />
                                </b-button>
                                <b-button
                                    v-if="index > 0"
                                    size="sm"
                                    variant="primary"
                                    :title="$gettext('Up')"
                                    @click.prevent="moveUp(index)"
                                >
                                    <icon icon="arrow_upward" />
                                </b-button>
                            </b-button-group>
                        </td>
                    </tr>
                </draggable>
            </b-table-simple>
        </b-overlay>
    </b-modal>
</template>

<script setup>
import Draggable from 'vuedraggable';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import InlinePlayer from '~/components/InlinePlayer';
import {ref} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/vendor/bootstrapVue";
import {useTranslate} from "~/vendor/gettext";

const loading = ref(true);
const reorderUrl = ref(null);
const media = ref([]);

const $modal = ref(); // Template Ref

const {axios} = useAxios();

const open = (newReorderUrl) => {
    reorderUrl.value = newReorderUrl;
    loading.value = true;
    $modal.value?.show();

    axios.get(newReorderUrl).then((resp) => {
        media.value = resp.data;
        loading.value = false;
    });
};

const {notifySuccess} = useNotify();
const {$gettext} = useTranslate();

const save = () => {
    let newOrder = {};
    let i = 0;

    media.value.forEach((row) => {
        i++;
        newOrder[row.id] = i;
    });

    axios.put(reorderUrl.value, {'order': newOrder}).then(() => {
        notifySuccess($gettext('Playlist order set.'));
    });
};

const moveDown = (index) => {
    media.value.splice(index + 1, 0, media.value.splice(index, 1)[0]);
    save();
};

const moveUp = (index) => {
    media.value.splice(index - 1, 0, media.value.splice(index, 1)[0]);
    save();
};

defineExpose({
    open
});
</script>

<script>

export default {
    data() {
        return {};
    },
    methods: {}
};
</script>

<style lang="scss">
table.sortable {
    cursor: pointer;
}
</style>
