var inlineScript = document.createElement('script');
inlineScript.textContent = "document.getElementById('output').innerHTML += ' and Inline Script';";
document.body.appendChild(inlineScript);