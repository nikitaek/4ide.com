class JSAntivirus {
            constructor() {
                this.observer = new MutationObserver(this.handleMutations.bind(this));
                this.scriptRegistry = [];
                this.overrideDOMMethods();
            }

            startObserving(targetNode) {
                this.observer.observe(targetNode, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    characterData: true
                });
            }

            handleMutations(mutations) {
                mutations.forEach((mutation) => {
                    //console.log('DOM changed:', mutation);
                });
            }
            overrideDOMMethods() {
                const methodsToOverride = [
                    'appendChild',
                    'removeChild',
                    'replaceChild',
                    'insertBefore',
                    'cloneNode',
                    //'setAttribute',
                    //'removeAttribute',
                ];

             
                methodsToOverride.forEach(method => {
                    const originalMethod = Element.prototype[method] || Node.prototype[method];
                    if (originalMethod) {
                        const self = this;

                        Element.prototype[method] = function(...args) {
                            console.log(args);
                            let trace = self.logChangev2(method);

                            if (args[0]?.tagName.toLowerCase() === 'script') {
                                trace = self.trackScriptInjection(args[0], trace);
                            }
                            // Adding a function to each element, to return the trace of who injected this element
                            args[0].showTrace = () => trace;

                            return originalMethod.apply(this, args);
                        };
                    }
                });

                // Handle innerHTML as a property
                const originalInnerHTMLDescriptor = Object.getOwnPropertyDescriptor(Element.prototype, 'innerHTML');
                const self = this;
                Object.defineProperty(Element.prototype, 'innerHTML', {
                    get: function() {
                        return originalInnerHTMLDescriptor.get.call(this);
                    },
                    set: function(value) {
                        self.logChangev2('innerHTML'); 
                        originalInnerHTMLDescriptor.set.call(this, value);
                    },
                    configurable: true,
                    enumerable: true
                });
            }

            logChangev2(methodName) {
                const stack = (new Error()).stack.split('\n');
                let trace = { log: [], blame: "" };

                console.warn(`Method: ${methodName} called`);
                console.log('Call Trace:');

                // Start from the 3rd line to skip the current function and the Error constructor
                for (let i = 2; i < stack.length; i++) {
                    const info = this.parseLogLine(stack[i].trim());

                    if(info) {
                        trace.log.push(info);
                        console.log(`  ${trace.log.length}. Function: ${info.function}, File: ${info.file}, Line: ${info.line}`);
                    }
                   
                }

                if (trace.log.length) {
                    const blameFile = trace.log[trace.log.length - 1].file;
                    const parentFile = this.getCallerFile(blameFile);
                    trace.blame = blameFile;

                    console.log("To blame: %c " + blameFile + " ", "font-weight:bold; background-color: #a6c4ff; padding:4px;");
                    if (parentFile && parentFile !== blameFile) {
                        console.log("Parent caller to blame is: %c " + parentFile, "font-weight:bold; background-color: #a6c4ff; padding:4px;");
                        trace.blame = parentFile;
                    }
                    console.log("");
                }

                return trace;
            }

            parseLogLine(line) {
                var logLineParse = /^\s*at (.*?) ?\(((?:file|https?|blob|chrome-extension|native|eval|webpack|<anonymous>|\/|[a-z]:\\|\\\\).*?)(?::(\d+))?(?::(\d+))?\)?\s*$/i;
                var chromeEvalRe = /\((\S*)(?::(\d+))(?::(\d+))\)/;

                const parts = logLineParse.exec(line);
                if (parts) {
                    const isEval = parts[2]?.startsWith('eval');
                    if (isEval) {
                        const submatch = chromeEvalRe.exec(parts[2]);
                        if (submatch) {
                            [parts[2], parts[3], parts[4]] = [submatch[1], submatch[2], submatch[3]];
                        }
                    }
                    return {
                        file: parts[2]?.startsWith('native') ? null : parts[2],
                        function: parts[1] || "unknown",
                        line: parts[3] ? +parts[3] : null,
                        column: parts[4] ? +parts[4] : null
                    };
                }

                    var searchForFile = /at\s+(.+?)(?::(\d+))?(?::(\d+))?$/;
                    const fileSearch = searchForFile.exec(line);

                    return fileSearch ? {
                        file: fileSearch[1],
                        function: "unknown",
                        line: fileSearch[2],
                        column: fileSearch[3]
                    } : null;
            }
           
            trackScriptInjection(element, trace) {
                trace.blame = this.getCallerFile(trace.log[trace.log.length - 1].file);
                this.scriptRegistry[element.src] = {
                    caller: trace.blame,
                    type: element.src ? "script" : "inline",
                    src: element.src
                };
                console.error(`Script injected: ${element.src || 'inline script'}`);
                console.log(`Injected by: ${trace.blame}`);
                console.log("");
                return trace;
            }

            getCallerFile(file) {
                return this.scriptRegistry[file]?.caller || file;
            }
        }

        const observer = new JSAntivirus();
        observer.startObserving(document.body);  