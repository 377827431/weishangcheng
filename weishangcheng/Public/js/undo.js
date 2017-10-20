(function(){
	var Undo = window.Undo = function(dom, options){
	    //统一兼容问题
	    var MutationObserver = this.MutationObserver = window.MutationObserver ||
	        window.WebKitMutationObserver ||
	        window.MozMutationObserver;

	    //判断浏览器是或否支持MutationObserver;
	    this.mutationObserverSupport = !!MutationObserver;

	    //默认监听子元素， 子元素的属性， 属性值的改变;
	    this.options = {
	        childList: true,
	        subtree: true,
	        attributes : true,
	        characterData : true,
	        attributeOldValue : true,
	        characterDataOldValue : true
	    };
	    
	    if(options){
	    	for(var k in options){
	    		this.options[k] = options[k]
	    	}
	    }

	    //这个保存了MutationObserve的实例;
	    this.muta = {};

	    //list这个变量保存了用户的操作;
	    this.list = [];

	    //当前回退的索引
	    this.index = -1;

	    //如果没有dom的话，就默认监听body;
	    this.dom = dom || document.documentElement.body || document.getElementsByTagName("body")[0];
	    
	    this._html = this.dom.innerHTML;

	    //马上开始监听;
	    this.observe();
	};

	Undo.prototype.callback = function(records , instance){
	    //要把索引后面的给清空;
	    this.list.splice(this.index+1);
	
	    var _this = this;
	    records.map(function(record){
	        var target = record.target;
	        //console.log(record);
	        //删除元素或者是添加元素;
	        if(record.type === "childList"){
	            //如果是删除元素;
	        	var index = -1;
	        	if(record.nextSibling){
	        		index = _this.indexOf(target.children, record.nextSibling);
            	}else if(record.previousSibling){
            		index = _this.indexOf(target.children, record.previousSibling);
            	}
	            
	            _this.list.push({
                    "undo" : function(){
                        _this.disconnect();
                        if(record.addedNodes.length > 0){
	                        _this.removeChildren(target, record.addedNodes);
                        }
                        if(record.removedNodes.length > 0){
                            _this.addChildren(target, record.removedNodes, index);
                        }
                        _this.reObserve();
                    },
                    "redo" : function(){
                        _this.disconnect();
                        if(record.addedNodes.length > 0){
                            _this.addChildren(target, record.addedNodes, index);
                        }
                        if(record.removedNodes.length > 0){
	                        _this.removeChildren(target, record.removedNodes);
                        }
                        _this.reObserve();
                    }
                });
	        }else if(record.type === "characterData"){
	            var oldValue = record.oldValue;
	            var newValue = record.target.textContent //|| record.target.innerText, 不准备处理IE789的兼容，所以不用innerText了;
	            _this.list.push({
	                "undo" : function(){
	                    _this.disconnect();
	                    target.textContent = oldValue;
	                    _this.reObserve();
	                },
	                "redo" : function(){
	                    _this.disconnect();
	                    target.textContent = newValue;
	                    _this.reObserve();
	                }
	            });
	            //如果是属性变化的话style, dataset, attribute都是属于attributes发生改变, 可以统一处理;
	        }else if(record.type === "attributes"){
	            var oldValue = record.oldValue;
	            var newValue = record.target.getAttribute(record.attributeName);
	            var attributeName = record.attributeName;
	            _this.list.push({
	                "undo" : function(){
	                    _this.disconnect();
	                    target.setAttribute(attributeName, oldValue);
	                    _this.reObserve();
	                },
	                "redo" : function(){
	                    _this.disconnect();
	                    target.setAttribute(attributeName, newValue);
	                    _this.reObserve();
	                }
	            });
	        };
	    });
	
	    //重新设置索引;
	    this.index = this.list.length-1;
	}
	
	Undo.prototype.removeChildren = function(target, nodes){
        for(var i= 0, len= nodes.length; i<len; i++){
            target.removeChild(nodes[i]);
        }
    };

	Undo.prototype.addChildren =  function(target, nodes, index){
		var existingnode = index > -1 ? target.children[index] : undefined;
		for(var i=0; i<nodes.length; i++){
			target.insertBefore(nodes[i], existingnode);
		}
    }

    //快捷方法,用来判断child在父元素的哪个节点上;
	Undo.prototype.indexOf = function(target, obj){
		return Array.prototype.indexOf.call(target, obj);
    }

	// 马上开始监听
	Undo.prototype.observe = function(){
        if(this.dom.nodeType !== 1)return alert("参数不对，第一个参数应该为一个dom节点");
        this.muta = new this.MutationObserver(this.callback.bind(this));
        this.muta.observe(this.dom, this.options);

    }

	// 重新开始监听
	Undo.prototype.reObserve = function(){
        this.muta.observe(this.dom, this.options)
    }

	// 不记录dom操作， 所有在这个函数内部的操作不会记录到undo和redo的列表中
	Undo.prototype.without = function(fn){
        this.disconnect();
        fn&fn();
        this.reObserve();
    }

	// 取消监听
	Undo.prototype.disconnect = function(){
        return this.muta.disconnect();
    }

	// 保存Mutation操作到list
    Undo.prototype.save = function(obj){
        if(!obj.undo)return alert("传进来的第一个参数必须有undo方法才行");
        if(!obj.redo)return alert("传进来的第一个参数必须有redo方法才行");
        this.list.push(obj);
    }

    // 清空数组
    Undo.prototype.reset = function(){
    	this.list = [];
        this.index = -1;
        
        this.disconnect();
        this.dom.innerHTML = this._html;
        this.reObserve();
    }

	// 把指定index后面的操作删除;
    Undo.prototype.splice = function(index){
        this.list.splice(index)
    }

	// 往回走， 取消回退
    Undo.prototype.undo = function(){
         if(this.canUndo()){
             this.list[this.index].undo();
             this.index--;
             return true;
         }
         return false;
    }

	// 往前走， 重新操作
    Undo.prototype.redo = function(){
        if(this.canRedo()){
            this.index++;
            this.list[this.index].redo();
            return true;
        }
        return false;
    }

	// 判断是否可以撤销操作
    Undo.prototype.canUndo = function(){
        return this.index !== -1
    }

	// 判断是否可以重新操作
    Undo.prototype.canRedo = function(){
        return this.list.length-1 !== this.index
    }
})();