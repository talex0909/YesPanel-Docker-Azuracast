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
            <form-artwork
                v-model="v$.artwork_file.$model"
                :artwork-src="record.links.art"
                :new-art-url="newArtUrl"
                :edit-art-url="record.links.art"
            />
        </b-tabs>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import FormBasicInfo from './Form/BasicInfo';
import FormSchedule from './Form/Schedule';
import FormArtwork from './Form/Artwork';
import mergeExisting from "~/functions/mergeExisting";
import {baseEditModalProps, useBaseEditModal} from "~/functions/useBaseEditModal";
import {computed, ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useResettableRef} from "~/functions/useResettableRef";
import ModalForm from "~/components/Common/ModalForm.vue";

const props = defineProps({
    ...baseEditModalProps,
    stationTimeZone: {
        type: String,
        required: true
    },
    newArtUrl: {
        type: String,
        required: true
    },
});

const emit = defineEmits(['relist']);

const $modal = ref(); // Template Ref

const {record, reset} = useResettableRef({
    has_custom_art: false,
    links: {}
});

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
    (formRef, formIsEditMode) => computed(() => {
        return {
            'streamer_username': {required},
            'streamer_password': (formIsEditMode.value) ? {} : {required},
            'display_name': {},
            'comments': {},
            'is_active': {},
            'enforce_schedule': {},
            'artwork_file': {},
            'schedule_items': {}
        };
    }),
    {
        'streamer_username': null,
        'streamer_password': null,
        'display_name': null,
        'comments': null,
        'is_active': true,
        'enforce_schedule': false,
        'schedule_items': [],
        'artwork_file': null
    },
    {
        resetForm: (originalResetForm) => {
            originalResetForm();
            reset();
        },
        populateForm: (data, formRef) => {
            record.value = data;
            formRef.value = mergeExisting(formRef.value, data);
        },
    },
);

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isEditMode.value
        ? $gettext('Edit Streamer')
        : $gettext('Add Streamer');
});

defineExpose({
    create,
    edit,
    close
});
</script>
