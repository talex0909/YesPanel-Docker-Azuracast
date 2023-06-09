<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="langTitle"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <b-tabs
            content-class="mt-3"
            pills
        >
            <form-basic-info :form="v$" />
            <form-schedule
                v-model:schedule-items="form.schedule_items"
                :form="v$"
                :station-time-zone="stationTimeZone"
            />
            <form-advanced
                v-if="enableAdvancedFeatures"
                :form="v$"
            />
        </b-tabs>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import FormAdvanced from './Form/Advanced';
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
    stationTimeZone: {
        type: String,
        required: true
    },
    enableAdvancedFeatures: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['relist', 'needs-restart']);

const $modal = ref(); // Template Ref

const {notifySuccess} = useNotify();

const {
    loading,
    error,
    isEditMode,
    form,
    v$,
    clearContents,
    create,
    edit,
    doSubmit,
    close
} = useBaseEditModal(
    props,
    emit,
    $modal,
    {
        'name': {required},
        'is_enabled': {},
        'include_in_on_demand': {},
        'weight': {},
        'type': {},
        'source': {},
        'order': {},
        'remote_url': {},
        'remote_type': {},
        'remote_buffer': {},
        'is_jingle': {},
        'play_per_songs': {},
        'play_per_minutes': {},
        'play_per_hour_minute': {},
        'include_in_requests': {},
        'avoid_duplicates': {},
        'backend_options': {},
        'schedule_items': {}
    },
    {
        'name': '',
        'is_enabled': true,
        'include_in_on_demand': false,
        'weight': 3,
        'type': 'default',
        'source': 'songs',
        'order': 'shuffle',
        'remote_url': null,
        'remote_type': 'stream',
        'remote_buffer': 0,
        'is_jingle': false,
        'play_per_songs': 0,
        'play_per_minutes': 0,
        'play_per_hour_minute': 0,
        'include_in_requests': true,
        'avoid_duplicates': true,
        'backend_options': [],
        'schedule_items': []
    },
    {
        onSubmitSuccess: () => {
            notifySuccess();
            emit('relist');
            emit('needs-restart');
            close();
        },
    }
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Playlist')
        : $gettext('Add Playlist');
});

defineExpose({
    create,
    edit,
    close
});
</script>
