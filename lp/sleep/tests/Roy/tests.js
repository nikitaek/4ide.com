document.getElementById('testAppendChild').onclick = testAppendChild;
document.getElementById('testRemoveChild').onclick = testRemoveChild;
document.getElementById('testSetInnerHTML').onclick = testSetInnerHTML;
document.getElementById('testSetAttribute').onclick = testSetAttribute;


function logTestRun(testName) {
    console.log("%c Running Test : "+testName, 'background: #222; color: #bada55; padding:8px');
}
function testAppendChild() {
    logTestRun("testAppendChild");
    const newDiv = document.createElement('div');
    newDiv.textContent = 'I am a new div';
    document.body.appendChild(newDiv);
}

function testRemoveChild() {
    logTestRun("testRemoveChild");
    const divToRemove = document.querySelector('div');
    if (divToRemove) {
        document.body.removeChild(divToRemove);
    } else {
        console.log('No div to remove');
    }
}

function testSetInnerHTML() {
    logTestRun("testSetInnerHTML");
    const newDiv = document.createElement('div');
    newDiv.textContent = 'I will be updated';
    document.body.appendChild(newDiv);
    newDiv.innerHTML = '<span>Updated content</span>';
}

function testSetAttribute() {
    logTestRun("testSetAttribute");
    const newDiv = document.createElement('div');
    newDiv.textContent = 'I will have an attribute';
    document.body.appendChild(newDiv);
    newDiv.setAttribute('data-test', 'testValue');
}
