## Redis stream based queue

### Features
events are consumed 
allow for multiple worker to co-work together.

### Explained

### Note
when register for event, use listener's class name (as string) to re-create the listener object each time. 
Or you may create the object to re-use the listener object across multiple event triggering. 