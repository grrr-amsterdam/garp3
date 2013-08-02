
Ext.ns('Ext.ux', 'Ext.ux.direct');

/**
 * @namespace   Ext.ux.direct
 * @class       Ext.ux.direct.ZendFrameworkProvider
 * @extends     Ext.direct.RemotingProvider
 * @author      Peter
 * @author      Based on work from Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Based on Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * Ext.Direct provider for seamless integration with Zend_Json_Server
 * 
 *  Ext.Direct.addProvider(Ext.apply(Ext.app.JSONRPC_API, {
        'type'     : 'zfprovider',
        'url'      : Ext.app.JSONRPC_API
    }));
 * 
 */
Ext.ux.direct.ZendFrameworkProvider = Ext.extend(Ext.direct.RemotingProvider, {
    
    // private
    getCallData: function(t){
        return {
            jsonrpc: '2.0',
            method: t.action + '.' + t.method,
            params: t.data,
            id: t.tid
        };
    },
    
	// private
    onData: function(opt, success, xhr) {
		var rpcresponse;
		try {
			rpcresponse = Ext.decode(xhr.responseText);
		} catch(e){
			if(console && console.error){
				console.error('Non-valid JSON encountered. Ignoring: ' + e.message || '');
				console.log(xhr.responseText);
			}	
		}
		
		// batch of results:
		if (Ext.isArray(rpcresponse)) {
			var rpcresponses = rpcresponse;

			xhr.responseText = [];
			Ext.each(rpcresponses, function(rpcresponse){
				xhr.responseText.push({
					type: rpcresponse ? rpcresponse.result ? 'rpc' : 'exception' : 'exception',
					error: rpcresponse ? rpcresponse.error ? rpcresponse.error : null : 'Network error',
					result: rpcresponse ? rpcresponse.result : null,
					tid: rpcresponse ? rpcresponse.id : null
				});
			});
		// single result:
		} else {
			xhr.responseText= {
				type: rpcresponse ? rpcresponse.result ? 'rpc' : 'exception' : 'exception',
				error: rpcresponse ? rpcresponse.error ? rpcresponse.error : null : 'Network error',
				result: rpcresponse ? rpcresponse.result : null,
				tid: rpcresponse ? rpcresponse.id : null
			};
		}
        Ext.ux.direct.ZendFrameworkProvider.superclass.onData.apply(this, arguments);
    }

});

Ext.Direct.PROVIDERS.zfprovider = Ext.ux.direct.ZendFrameworkProvider;
