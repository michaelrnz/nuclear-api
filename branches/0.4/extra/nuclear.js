/*
	Nuclear API JSON Interface
	based on source from melative.com
	2009 Spring
*/

function NuclearMethod(o)
{ 
	this.addStatics(o);
}

NuclearMethod.prototype =
{
	addStatics: function(a){
		this.fieldSet = [];
		this._data = [];

		// for each static field in a
		for( var i in a )
		{
			// ensure null method
			this[ i ] = null;

			// check for absolutely fixed
			if( a[i] )
			{
				this.getset(i, a[i]);
			}
			else // create method in the class
			{
				this[ i ] = this.buildGetSet( i );
			}

			// check if it was fixed, if not 
			if( this.fieldSet.indexOf(i)==-1 )
				this.fieldSet.push( i );
		}
	},

	addFields: function(a){
		for( var i in a )
		{
			if( !this.fieldSet )
				this.fieldSet = [];

			if( this.fieldSet.indexOf(a[i])==-1 )
				this.fieldSet.push( a[i] );

			if( !this[ a[i] ] )
				this[ a[i] ] = this.buildGetSet( a[i] );
		}
	},
	
	setFields: function(o)
	{
		for( var i in o )
		{
			if( this[i] && o[i] )
			{
				this[ i ]( o[i] );
			}
		}
	},

	getset: function(v, d)
	{
		if(d)
		{
			this._data[v] = d;
		}
		else
		{
			return this._data[v];
		}
	},

	buildGetSet: function( field )
	{
		return function(d){ return this.getset(field, d); }
	},

	data: function( field )
	{
		return this._data[field];
	},

	escape: function(v)
	{
		switch( typeof(v) )
		{
			case 'string':
				return v.replace(/\"/g, '\\\"').replace(/\r?\n/g, '\\n').replace(/^([^\n]+)$/, '\"$1\"');

			case 'number':
			case 'boolean':
				return v.toString();

			case 'object':
				// to be made recursive later
		}
	},

	toString: function()
	{
		// check for operation
		var op = this.data('op');
		if( !op ) return "";

		// check for callback
		var cb = this.data('callback');

		// get fields
		var fields = [];
		for( var f in this.fieldSet )
		{
			// the field value
			fv = this._data[ this.fieldSet[f] ];
			if( fv != undefined )
			{
				// get the escaped field
				var ef = this.escape( this.fieldSet[f] ) + ":";

				// get the escaped value
				var ev= this.escape( fv );

				// test value
				if( ev != undefined || ev != null )
				{
					ef += ":" + ev 
					fields.push( ef );
				}
			}
		}

		// return op-call-callback
		return "op=" + op + "&call={" + fields.toString() + "}" + (cb ? "&callback=" + cb : "");
	}
}

// builder API
function NuclearBuildAPI( API )
{
	this.build( API );
}

BuildNuclearAPI.prototype = {

	// returns function based on statics and fields of API method
	create: function(statics, fields)
	{
		return function(o){ NuclearMethod.call(this, statics); if( fields ){ this.addFields(fields) }; this.setFields(o); };
	},

	// link the method C to the api var
	link: function(api, c)
	{
		var f = api[c]['f'] || null;
		var s = api[c].s;
		api[N] = this.create(s,f);
		api[N].prototype = new NuclearMethod;
		api[N].prototype.fieldSet = [];
	},

	// build the api methods into the variable
	build: function( api )
	{
		// check for callers 
		if( typeof(api['callers']) != 'object'  )
			return;

		for(var c in api.callers)
		{
			this.link( api, c );
		}
	}

};

function MyStatics(pre)
{
	var r = {op:null,id:null,media:null,title:null,context_type:null,context_name:null,
		user:null,creator:null,character:null};
	
	for(var i in pre)
	{
		r[i] = pre[i];
	}

	return r;
}

function MyStaticsAll(pre)
{
	var r = {op:null,id:null,media:null,title:null,context_type:null,context_name:null,
		anime:null,adrama:null,art:null,blog:null,comic:null,film:null,lightnovel:null,
		literature:null,manga:null,music:null,periodical:null,stage:null,tv:null,vg:null,
		user:null,creator:null,character:null};

	for(var i in pre)
	{
		r[i] = pre[i];
	}

	return r;
}
	

var MyAPI = {

	callers: {
		Insert: {s:MyStatics({op:'insert'}), f:['year','country','season']},

		Relativity: {s:MyStaticsAll({op:'relativity'}), f:['level']},
		Reflection: {s:MyStaticsAll({op:'reflection'}), f:['subject','basis','range','text','moment']},

		Recommends: {s:{op:'recommend',id:false,U:false,M:false,T:false}, f:['reason','flush']},
		Relation: {s:{op:'relation',M:false,T:false}, f:['media','title','relation','note']},

		Experience: {s:{op:'experience'}, f:['active']},
		Tag: {s:{op:'tag',id:false,U:false,M:false,T:false}, f:['tags','tag','flush']},
		//Chatter: {s:{op:'chatter',id:false,U:false,M:false,T:false}, f:['msg']},

		//FriendRequest: {s:{op:'friend.request',U:false}, f:['pending']},
		//FriendAuthorize: {s:{op:'friend.authorize',U:false}, f:null },
		//FriendBe: {s:{op:'friend.be',U:false}, f:['msg']},

		Images: {s:{op:'images',U:false,M:false,T:false}, f:null },
		ImagePost: {s:{op:'image',id:false,M:false,T:false}, f:['uri']},


		Linkage: {s:{op:'linkage',U:false,M:false,T:false}, f:['uri','title']},
		Messages: {s:{op:'messages'}, f:null },
		Meta: {s:{op:'meta',id:false,M:false,T:false}, f:['field','base','production','description','standards','characters','tracks','quotes']},


		Suggest: {s:{op:'suggest',id:false,U:false,M:false,T:false}, f:['media','title','reason']},
		Synonyms: {s:{op:'synonyms',id:false,M:false,T:false}, f:['synonyms']},

		WishUpdate: {s:{op:'wish.update'}, f:['wid','state','ts','basis','range','note']},
		WishRemove: {s:{op:'wish.remove'}, f:['wid']},
		Wish: {s:{op:'wish',id:false,M:false,T:false}, f:['basis','range','note','interval','repeat','index','moment']}
	}
};

// build the API
BuildNuclearAPI( MyAPI );
