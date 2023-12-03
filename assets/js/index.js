'use strict';

import { shaderEffect } from "./shader-effect.js";

const fragment = `
#version 100

#ifdef GL_ES
    precision highp float;
    precision highp int;
#endif

uniform float time;
uniform vec4 color;
uniform vec2 resolution;

vec2 size = vec2(resolution/15.0);
vec2 s = vec2(1, 1.7320508);
vec4 background = vec4(0.,0.,0.,1.);
float strokeWidth = 0.1;
float flatSide = 1.0;

float plasma(vec2 uv, float scale, float time) {
    float v = cos(uv.x*uv.y * scale) - cos(uv.x/(0.4+uv.y) + time);
    float f = floor(v);
    float c = ceil(v);
    return min(pow(min(c-v, v-f) * 1.8, 0.7), 1.0);
}

vec4 wave(vec4 c) {
    vec2 cntr = vec2(0.5);
    vec2 uv = (gl_FragCoord.xy/resolution - 0.5)/1.5;
    float scale = 25.0; uv.y *= 0.6;
    float r0 = plasma(uv, scale, time);
    float r1 = plasma(uv, scale * 2.0, time * 1.5 + 0.32);
    float r = r0 * r1 * 1.0 - pow(length(cntr) * 0.8, 6.0);
    return mix(vec4(vec3(0.0), 1.0), c, r);
}

float hash21(vec2 p) {
    return fract(sin(dot(p, vec2(141.13, 289.97))) * 43758.5453);
}

float hex(in vec2 p) {
    p = abs(p);
    float _p = mix(p.y, p.x, flatSide);
    return max(dot(p, s * 0.5), _p);
}

vec4 getHex(vec2 p) {
    vec2 v = vec2(1.0, 0.5);
    vec4 hC = floor(vec4(p, p - mix(v.xy, v.yx, flatSide))/vec4(s.xy,s.xy)) + 0.5;
    vec4 h = vec4(p - hC.xy * s, p - (hC.zw + 0.5)*s);
    return dot(h.xy, h.xy) < dot(h.zw, h.zw) ? vec4(h.xy, hC.xy) : vec4(h.zw, hC.zw + 0.5);
}

void main() {
    vec2 uv = gl_FragCoord.xy/resolution * size;
    vec4 h = getHex(uv + s);
    float eDist = hex(h.xy);
    gl_FragColor = mix(background, wave(color), smoothstep(0.0, 0.03, eDist - 0.5 + strokeWidth/2.));
}
`;

window.addEventListener("load", function() {
    const pages = document.getElementsByClassName('page');
    const shaders = [];
    let time = 0;

    this.setInterval(window.requestAnimationFrame, 100, _ => {
        time += 0.02;
        shaders.forEach((sh, i) => sh.update([0.45,0.73,0.98,1.0], time + i * 2));
    });

    /**
     * @param {HTMLDivElement} el
     */
    [...pages].forEach(element => {
        const canvas = element.getElementsByTagName('canvas')[0];

        if(canvas) shaders.push(new shaderEffect(canvas, fragment));

        element.onmouseout = () => {
            element.style.transform = `perspective(180px) rotateX(0deg) rotateY(0deg)`;
        };

        element.onmousemove = e => {
            const dx = -8 * (e.offsetX/element.clientWidth - 0.5);
            const dy = 8 * (e.offsetY/element.clientHeight - 0.5);
            element.style.transform = `perspective(180px) rotateX(${dy.toFixed(1)}deg) rotateY(${dx.toFixed(1)}deg)`;
        };
    });
}, false);