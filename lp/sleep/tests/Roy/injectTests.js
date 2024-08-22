// Scenario 1: Injects inline JavaScript from the HTML
document.getElementById('inline-js').addEventListener('click', function() {

    logTestRun("Scenario 1: Injects inline JavaScript from the HTML");

    const script = document.createElement('script');
    script.innerHTML = "document.getElementById('output').innerHTML = 'Changed by Inline JS';";
    document.body.appendChild(script);
});

// Scenario 2: Injects an external.js file which does the DOM change
document.getElementById('external-js').addEventListener('click', function() {

    logTestRun("Scenario 2: Injects an external2.js file which does the DOM change");

    const script = document.createElement('script');
    script.src = 'external2.js';
    document.body.appendChild(script);
});

// Scenario 3: Injects external3.js which injects inline script
document.getElementById('external-inline-js').addEventListener('click', function() {
    
    logTestRun("Scenario 3: Injects external.js which injects inline script");

    const script = document.createElement('script');
    script.src = 'external3.js';
    document.body.appendChild(script);
});

// Scenario 4: Inject external4.js which injects external2.js
document.getElementById('external-external-js').addEventListener('click', function() {

    logTestRun("Scenario 4: Inject external4.js which injects external2.js");

    const script = document.createElement('script');
    script.src = 'external4.js';
    document.body.appendChild(script);
});


// Scenario 5: Injects inline JavaScript function and runs the function
document.getElementById('inline-js2').addEventListener('click', function() {

    logTestRun("Scenario 5: Injects inline JavaScript function and runs the function");

    const script = document.createElement('script');
    script.innerHTML = "function xxx() {document.getElementById('output').innerHTML = 'Changed by Inline JS'; document.getElementById('output').innerHTML = 'Changed by Inline JS';} xxx();";
    document.body.appendChild(script);

    xxx();
});

// Scenario 6: Inject external5.js which have setTimeout for function calling
document.getElementById('external-6').addEventListener('click', function() {

    logTestRun("Scenario 6: Inject external6.js which have setTimeout for function calling");

    const script = document.createElement('script');
    script.src = 'external6.js';
    document.body.appendChild(script);
});