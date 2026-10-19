import assert from 'node:assert/strict';
import test from 'node:test';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../../resources/js/ui/navigation.js', import.meta.url), 'utf8').replace(/export /g, '');
test('mobile drawer wraps keyboard focus while open, including when aria-hidden is absent', () => {
    const listeners = {};
    const document = { activeElement: null,
        getElementById: id => ({'main-content':main, 'main-sidebar':sidebar, 'sidebar-menu':{dataset:{scrollKey:'sidebar-scroll-1'}}})[id],
        querySelector: () => null, addEventListener: (name, handler) => { listeners[name] = handler; },
    };
    const first = { getClientRects: () => [{}], focus() {document.activeElement=this;} };
    const last = { getClientRects: () => [{}], focus() {document.activeElement=this;} };
    const main = {scrollTop:0};
    const sidebar = {inert:false, querySelectorAll:()=>[first,last], contains:element=>[first,last].includes(element)};
    const context = vm.createContext({document, URL, location:{origin:'https://warehouse.test',pathname:'/dashboard',search:''},
        sessionStorage:{getItem:()=>null}, window:{addEventListener(){}}, matchMedia:()=>({matches:true})});
    vm.runInContext(source, context); context.initNavigation();
    let prevented = false;
    document.activeElement = first;
    listeners.keydown({key:'Tab',shiftKey:true,preventDefault(){prevented=true;}});
    assert.equal(prevented,true); assert.equal(document.activeElement,last);
    listeners.keydown({key:'Tab',shiftKey:false,preventDefault(){}});
    assert.equal(document.activeElement,first);
    sidebar.inert=true; prevented=false;
    listeners.keydown({key:'Tab',shiftKey:true,preventDefault(){prevented=true;}});
    assert.equal(prevented,false);
});
