function route(name, params, absolute, config = window.Ziggy) {
    const url = new URL(config.url);
    
    if (!config.routes[name]) {
        console.error(`Route ${name} not found`);
        return '';
    }
    
    const route = config.routes[name];
    let domain = route.domain || '';
    let uri = route.uri || '';
    
    // Substituir parâmetros na URI
    if (params) {
        Object.keys(params).forEach(key => {
            uri = uri.replace(`{${key}}`, params[key]);
            uri = uri.replace(`{${key}?}`, params[key]);
        });
    }
    
    // Remover parâmetros opcionais não preenchidos
    uri = uri.replace(/\{[^}]+\?\}/g, '');
    
    const fullUrl = `${url.origin}/${uri}`.replace(/([^:]\/)\/+/g, '$1');
    
    return absolute ? fullUrl : `/${uri}`;
}

// Helper para verificar se está na rota atual
route.current = function(name, params) {
    const currentUrl = window.location.pathname;
    const config = window.Ziggy;
    
    if (!config.routes[name]) return false;
    
    const route = config.routes[name];
    let uri = route.uri || '';
    
    // Verificação simples
    if (name.includes('*')) {
        const prefix = name.replace('.*', '');
        return currentUrl.includes(`/${config.routes[prefix]?.uri || prefix}`);
    }
    
    return currentUrl === `/${uri}` || currentUrl.startsWith(`/${uri}/`);
};

export default route;