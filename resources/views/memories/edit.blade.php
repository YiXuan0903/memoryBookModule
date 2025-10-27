@extends('layouts.app')
@section('title','Edit Memory')

@section('content')
<div class="card">
  <div class="card-body">
    <h3>Edit Memory</h3>

    <div class="mb-3">
      <label class="form-label">Voice Input Language</label>
      <select id="voiceLang" class="form-select w-auto d-inline" onchange="changeLang(this.value)">
        <option value="auto" selected>🌐 Auto Detect</option>
        <option value="en-US">English (US)</option>
        <option value="ms-MY">Bahasa Melayu (MY)</option>
        <option value="zh-CN">中文 (Simplified)</option>
        <option value="zh-TW">中文 (Traditional)</option>
      </select>
    </div>

    <form method="POST" action="/memories/{{ $memory->id }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div class="mb-3">
        <label class="form-label">Title</label>
        <div class="input-group">
          <input type="text" id="titleField" name="title" class="form-control"
                 value="{{ old('title', $memory->title) }}" required>
          <button type="button" class="btn voice-btn" onclick="startVoice('titleField', this)">🎤</button>
        </div>
        <small id="status-titleField" class="voice-status"></small>
      </div>

      <div class="mb-3">
        <label class="form-label">Content</label>
        <div class="input-group">
          <textarea id="contentField" name="content" class="form-control" rows="5">{{ old('content', $memory->content) }}</textarea>
          <button type="button" class="btn voice-btn" onclick="startVoice('contentField', this)">🎤</button>
        </div>
        <small id="status-contentField" class="voice-status"></small>
      </div>

      <div class="mb-3">
        <label class="form-label">Upload (image/audio/video)</label>
        <input type="file" name="file" class="form-control">
        @if($memory->file_path)
          <small class="text-muted">Current file: {{ basename($memory->file_path) }}</small>
        @endif
      </div>

      <div class="mb-3">
        <label class="form-label">Mood</label>
        <select name="mood" class="form-select">
          <option value="">Select mood</option>
          @foreach(['happy','sad','angry','excited','calm','tired'] as $m)
            <option value="{{ $m }}" {{ old('mood', $memory->mood) == $m ? 'selected' : '' }}>
              {{ ucfirst($m) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Tags (comma separated)</label>
        <div class="input-group">
          <input type="text" id="tagsField" name="tags" class="form-control"
                 value="{{ old('tags', $memory->tags) }}">
          <button type="button" class="btn voice-btn" onclick="startVoice('tagsField', this)">🎤</button>
        </div>
        <small id="status-tagsField" class="voice-status"></small>
      </div>

      <div class="mb-3">
        <label class="form-label">Template</label>
        <select name="template" class="form-select">
          <option value="">Select Template</option>
          <option value="daily" {{ old('template', $memory->template) == 'daily' ? 'selected' : '' }}>📝 Daily Journal</option>
          <option value="travel" {{ old('template', $memory->template) == 'travel' ? 'selected' : '' }}>✈️ Travel Diary</option>
          <option value="gratitude" {{ old('template', $memory->template) == 'gratitude' ? 'selected' : '' }}>🙏 Gratitude Log</option>
          <option value="study" {{ old('template', $memory->template) == 'study' ? 'selected' : '' }}>📚 Study Notes</option>
        </select>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_public" value="1" id="publicCheck"
               {{ old('is_public', $memory->is_public) ? 'checked' : '' }}>
        <label class="form-check-label" for="publicCheck">Make Public</label>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">Update</button>
        <a href="/memories" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
let recognition;
let listening = false;
let activeField = null;
let activeBtn = null;
let currentLang = "auto";
let finalBuffer = "";

function changeLang(langCode){
    currentLang = langCode;
    if(recognition) recognition.lang = (currentLang === "auto") ? navigator.language : currentLang;
}

if('webkitSpeechRecognition' in window){
    recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = true;

    recognition.onresult = function(event){
        let interim = '';
        for(let i=event.resultIndex;i<event.results.length;i++){
            let transcript = event.results[i][0].transcript;
            if(event.results[i].isFinal){
                finalBuffer += transcript + " ";
            } else {
                interim += transcript;
            }
        }
        if(activeField){
            let field = document.getElementById(activeField);
            let status = document.getElementById("status-" + activeField);
            field.value = (finalBuffer + interim).trim();
            status.innerText = interim ? "🟡 Listening (partial: " + interim + ")" : (listening ? "🔴 Listening..." : "");
        }
    };

    recognition.onerror = function(event){
        alert('Speech recognition error: ' + event.error);
    };

    recognition.onend = function(){
        if(listening) recognition.start();
    };
} else {
    document.querySelectorAll('.voice-btn').forEach(btn => {
        btn.disabled = true;
        btn.innerText = '🎤 Not Supported';
    });
}

function startVoice(fieldId, btn){
    if(!recognition) return;
    recognition.lang = (currentLang === "auto") ? navigator.language : currentLang;

    document.querySelectorAll(".voice-status").forEach(el=>el.innerText="");

    if(listening && activeField===fieldId){
        recognition.stop();
        listening=false;
        btn.innerText="🎤";
        document.getElementById("status-"+fieldId).innerText="";
        activeField=null;
        activeBtn=null;
        finalBuffer="";
    } else {
        if(listening && activeBtn){
            recognition.stop();
            activeBtn.innerText="🎤";
            if(activeField) document.getElementById("status-"+activeField).innerText="";
        }
        activeField = fieldId;
        activeBtn = btn;
        finalBuffer="";
        recognition.start();
        listening=true;
        btn.innerText="🔴 Stop";
        document.getElementById("status-"+fieldId).innerText="🔴 Listening...";
    }
}
</script>

<style>
.voice-btn{
    color:#fff;
    border-radius:8px;
    transition:0.3s;
    background-color:#0d6efd;
}
.voice-btn:hover{background-color:#0b5ed7;}
.voice-btn.recording{background-color:red;}
.voice-status{
    display:block;
    font-size:0.9em;
    margin-top:4px;
    font-weight:bold;
}
body.dark-theme .voice-btn{background-color:#4f5bff;}
body.dark-theme .voice-btn:hover{background-color:#3a45d0;}
body.pastel-theme .voice-btn{background-color:#f78da7;}
body.pastel-theme .voice-btn:hover{background-color:#f65a85;}
</style>
@endsection