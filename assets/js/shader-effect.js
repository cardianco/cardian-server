'use strict';

class shaderEffect {
    vertex =
       `#version 100
        attribute vec2 position;
        void main() { gl_Position = vec4(position, 0.0, 1.0); }`;

    fragment =
        `#version 100
        precision highp float;
        uniform float time;
        uniform vec4 color;
        void main() { gl_FragColor = color; }`;
    /**
     * @typedef {Element} logElement
     */
    logElement = undefined;

    /**
     * @param {HTMLCanvasElement} canvas
     * @param {String} fragment
     * @param {String} vertex
     */
    constructor(canvas, fragment = undefined, vertex = undefined, logElement = undefined) {
        const gl = canvas.getContext("webgl") || canvas.getContext("experimental-webgl");
        this.gl = gl;

        if(fragment) this.fragment = fragment;
        if(vertex) this.vertex = vertex;
        if(logElement) this.logElement = logElement;

        gl.canvas.width = canvas.clientWidth * window.devicePixelRatio;
        gl.canvas.height = canvas.clientHeight * window.devicePixelRatio;

        this.setupWebGL(gl, this.fragment, this.vertex);
    }

    /**
     * @param {RenderingContext} gl
     * @param {String} fragment
     * @param {String} vertex
     */
    setupWebGL(gl, fragment, vertex) {
        const vertexShader = gl.createShader(gl.VERTEX_SHADER);
        gl.shaderSource(vertexShader, vertex);
        gl.compileShader(vertexShader);

        const fragmentShader = gl.createShader(gl.FRAGMENT_SHADER);
        gl.shaderSource(fragmentShader, fragment);
        gl.compileShader(fragmentShader);

        const program = gl.createProgram();
        this.program = program;

        gl.attachShader(program, vertexShader);
        gl.attachShader(program, fragmentShader);
        gl.linkProgram(program);

        const linkStatus = gl.getProgramParameter(program, gl.LINK_STATUS);
        if (!gl.getProgramParameter(program, gl.LINK_STATUS)) {
            const programLog = gl.getProgramInfoLog(program);
            const fragmentLog = gl.getShaderInfoLog(fragmentShader);
            const vertexLog = gl.getShaderInfoLog(vertexShader);
            this.cleanup(gl, program);
            const errorText = `Shader program did not link successfully.\nError log: ${programLog}\n${fragmentLog}\n${vertexLog}`;
            if(this.logElement) this.logElement.innerHTML = errorText;
            console.error(errorText);
        }

        // NOTE: good practice to manage resources efficiently.
        gl.detachShader(program, vertexShader);
        gl.detachShader(program, fragmentShader);
        gl.deleteShader(vertexShader);
        gl.deleteShader(fragmentShader);

        if(linkStatus) {
            this.update();
        }
    }

    update(color = [0.9, 0.5, 0.1, 1.0], time = 1.0) {
        const gl = this.gl;
        const program = this.program;

        const timeLocation = gl.getUniformLocation(program, "time");
        const colorLocation = gl.getUniformLocation(program, "color");
        const resolutionLocation = gl.getUniformLocation(program, "resolution");
        const positionAttributeLocation = gl.getAttribLocation(program, "position");

		gl.clearColor(1.0, 1.0, 0.0, 1.0);
		gl.enable(gl.DEPTH_TEST);
		gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
        gl.viewport(0, 0, gl.canvas.width, gl.canvas.height);
        // Init attributes
        // Turn on the attribute
        gl.enableVertexAttribArray(positionAttributeLocation);

        this.buffer = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, this.buffer);

        // Tell the attribute how to get data out of positionBuffer (ARRAY_BUFFER)
        const array = new Float32Array([
            -1.0, -1.0,
            +1.0, -1.0,
            +1.0, +1.0,
            -1.0, +1.0
        ]);
        gl.vertexAttribPointer(positionAttributeLocation, 2, gl.FLOAT, false, 0, 0);
        gl.bufferData(gl.ARRAY_BUFFER, array, gl.STATIC_DRAW);

        // Linking the program.
        gl.useProgram(program);

        // Should be after linking the program
        gl.uniform1f(timeLocation, time);
        gl.uniform4fv(colorLocation, color);
        gl.uniform2fv(resolutionLocation, [gl.canvas.width, gl.canvas.height]);

        gl.drawArrays(gl.TRIANGLE_FAN, 0, 4);
    }

    cleanup() {
        this.gl.useProgram(null);
        if (this.buffer) { gl.deleteBuffer(this.buffer); }
        if (this.program) { gl.deleteProgram(this.program); }
    }
}

export {shaderEffect};