const fileInput=document.querySelector('#file'), statusBox=document.querySelector('#status'), details=document.querySelector('#details');
let currentJob=null; const csrf=document.querySelector('meta[name="csrf-token"]').content;
const showError=(message)=>{statusBox.textContent=message;statusBox.className='error';};
fileInput.addEventListener('change',async()=>{
  details.hidden=true;currentJob=null;statusBox.className='';statusBox.textContent='';const file=fileInput.files[0];if(!file)return;
  statusBox.textContent='Checking file and available formats…';const body=new FormData();body.append('file',file);
  try{const response=await fetch('api.php?action=inspect',{method:'POST',headers:{'X-CSRF-Token':csrf},body});const data=await response.json();if(!response.ok)throw new Error(data.error||'Could not inspect file.');
    currentJob=data.id;document.querySelector('#detected').textContent=`Detected ${data.kind} · ${data.mime}`;const select=document.querySelector('#format');select.replaceChildren();
    for(const [value,label] of Object.entries(data.formats)){const option=document.createElement('option');option.value=value;option.textContent=label;select.append(option);}details.hidden=false;statusBox.textContent=`Ready: ${data.name}`;
  }catch(error){showError(error.message);}
});
document.querySelector('#convert').addEventListener('click',async()=>{
  if(!currentJob)return;statusBox.className='';statusBox.textContent='Converting…';const body=new FormData();body.append('id',currentJob);body.append('format',document.querySelector('#format').value);
  try{const response=await fetch('api.php?action=convert',{method:'POST',headers:{'X-CSRF-Token':csrf},body});const data=await response.json();if(!response.ok)throw new Error(data.error||'Conversion failed.');
    const link=document.createElement('a');link.href=`${data.download}&token=${encodeURIComponent(csrf)}`;link.textContent='Download converted file';link.className='download';statusBox.replaceChildren(document.createTextNode('Conversion complete. '),link);
  }catch(error){showError(error.message);}
});
