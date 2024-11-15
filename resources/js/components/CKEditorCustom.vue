<script>
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

class MyUploadAdapter {
  constructor(loader) {
    this.loader = loader;
    this.url = `${window.baseURL}/api/upload-image`; // URL API để upload ảnh
  }

  // Phương thức này sẽ được gọi khi file bắt đầu được upload
  upload() {
    return this.loader.file
      .then(file => new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('image', file);

        // Thực hiện request upload
        fetch(this.url, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data && data.url) {
            resolve({
              default: data.url // Trả về URL của ảnh đã upload
            });
          } else {
            reject(data);
          }
        })
        .catch(error => {
          reject(error);
        });
      }));
  }

  // Phương thức này được gọi khi upload bị hủy
  abort() {
    // Bạn có thể xử lý nếu cần thiết (ví dụ: hủy request upload)
  }
}

export default {
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    config: {
      type: Object,
      default: () => ({
        toolbar: [
          'heading', '|', 'bold', 'italic', 'underline', 'strikethrough', 'link',
          'bulletedList', 'numberedList', 'blockQuote', 'imageUpload', 'insertTable', 'mediaEmbed',
          'alignment', 'fontFamily', 'fontSize', 'fontColor', 'fontBackgroundColor', '|',
          'undo', 'redo', 'highlight', 'horizontalLine', 'removeFormat', '|',
          'alignment:left', 'alignment:center', 'alignment:right', 'alignment:justify'
        ],
        extraPlugins: [function (editor) {
          editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
            return new MyUploadAdapter(loader);
          };
        }]
      })
    }
  },
  data() {
    return {
      editor: ClassicEditor,
      localValue: this.modelValue
    };
  },
  watch: {
    modelValue(newValue) {
      this.localValue = newValue;
    },
    localValue(newValue) {
      this.$emit('update:modelValue', newValue);
    }
  }
};
</script>

<template>
  <ckeditor
    :editor="editor"
    :config="config"
    v-model="localValue"
  ></ckeditor>
</template>

<style scoped>
.ck-editor__editable_inline {
  min-height: 500px;
}
</style>
