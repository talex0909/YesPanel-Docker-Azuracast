<template>
    <b-modal
        id="request_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('Request a Song')"
        hide-footer
    >
        <song-request
            :show-album-art="showAlbumArt"
            :request-list-uri="requestListUri"
            :custom-fields="customFields"
            @submitted="doClose"
        />
    </b-modal>
</template>

<script setup>
import SongRequest from '../Requests';
import {ref} from "vue";

const props = defineProps({
    requestListUri: {
        type: String,
        required: true
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
    customFields: {
        type: Array,
        required: false,
        default: () => []
    }
});

const $modal = ref(); // BModal

const doClose = () => {
    $modal.value?.hide();
};
</script>
